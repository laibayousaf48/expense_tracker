<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    // public function index()
    // {
    //     $budgets = Auth::user()->budgets()->get();
    //     if($budgets->isEmpty()){
    //         return response()->json(['message' => 'No budgets found'], 404);
    //     }else{
    //         return response()->json(['budgets' => $budgets]);
    //     }
    // }

    public function index()
    {
        try {
            $userId = Auth::id();
            
            // Step 1: Fetch categories with budgets for the logged-in user
            $categoriesWithBudgets = Category::where('user_id', $userId)
                ->with(['budget' => function($query) use ($userId) {
                    $query->where('user_id', $userId);
                }])
                ->get();
    
            // Step 2: Return response with category and budget details
            return response()->json([
                'data' => $categoriesWithBudgets,
                'message' => 'Categories with budget details fetched successfully',
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors during retrieval
            Log::error('Failed to fetch categories with budget details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch data', 'details' => $e->getMessage()], 500);
        }
    }
    


    public function store(Request $request)
{
    // Step 1: Validate incoming request data
    try {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['error' => 'Validation error', 'details' => $e->validator->errors()], 422);
    }

    // Step 2: Get the authenticated user
    $userId = Auth::id();

    // Step 3: Find the category by name or create it if it doesn't exist
    $category = Category::firstOrCreate(
        ['name' => $validatedData['name']],
        ['user_id' => $userId]
    );

    // Step 4: Check if a budget already exists for this user and category
    try {
        $budget = Budget::where('user_id', $userId)
            ->where('category_id', $category->id)
            ->first();

        if ($budget) {
            // If a budget already exists, update the amount and adjust the remaining amount
            $budget->amount = $validatedData['amount'];
            $budget->remaining = $validatedData['amount'] - $budget->spent;
            $budget->save();

            return response()->json(['message' => 'Budget updated successfully', 'budget' => $budget], 200);
        } else {
            // If no budget exists, create a new budget entry
            $newBudget = Budget::create([
                'user_id' => $userId,
                'category_id' => $category->id,
                'amount' => $validatedData['amount'],
                'spent' => 0, // Initial spent is 0 for new budgets
                'remaining' => $validatedData['amount'], // Initial remaining is the full amount
            ]);

            return response()->json(['message' => 'Budget created successfully', 'budget' => $newBudget], 201);
        }
    } catch (\Exception $e) {
        // Handle any errors that occur during budget creation or update
        Log::error('Failed to create or update budget', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to create or update budget', 'details' => $e->getMessage()], 500);
    }
}

 //deleting a budget   
 public function destroy(Request $request)
 {
     // Step 1: Validate incoming request data for budget ID
     $request->validate([
         'budget_id' => 'required|integer|exists:budgets,id',
     ]);
 
     try {
         $userId = Auth::id();
         
         // Step 2: Find the budget by ID and user ID to ensure user owns it
         $budget = Budget::where('id', $request->budget_id)
                         ->where('user_id', $userId)
                         ->firstOrFail();
 
         // Get the category ID before deleting the budget
         $categoryId = $budget->category_id;
 
         // Step 3: Delete the budget
         $budget->delete();
 
         // Step 4: Check if there are any other budgets associated with this category
         $otherBudgets = Budget::where('category_id', $categoryId)->where('user_id', $userId)->exists();
 
         if (!$otherBudgets) {
             // No other budgets found for this category, delete the category as well
             Category::where('id', $categoryId)->where('user_id', $userId)->delete();
         }
 
         return response()->json(['message' => 'Budget and associated category deleted successfully'], 200);
 
     } catch (\Exception $e) {
         // Handle any errors that occur during the delete process
         Log::error('Failed to delete budget and/or category', ['error' => $e->getMessage()]);
         return response()->json(['error' => 'Failed to delete budget and/or category', 'details' => $e->getMessage()], 500);
     }
 }
 
    
}