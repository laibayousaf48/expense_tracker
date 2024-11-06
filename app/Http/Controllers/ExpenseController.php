<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    //fetch without category

    // public function index()
    // {
    //     $expenses = Auth::user()->expenses;
    //     if($expenses->isEmpty()){
    //         return response()->json(['message' => 'No expenses found'], 404);
    //     }else{
    //         return response()->json($expenses);
    //     }
    // }

    //fetch all expenses using category
    public function index()
    {
        $userId = Auth::id();
        $expenses = Expense::with('category') // Eager load the category
            ->where('user_id', $userId)
            ->paginate(10);

        if ($expenses->isEmpty()) {
            return response()->json(['message' => 'No expenses found'], 404);
        }

        return response()->json($expenses);
    }

    // create new expense


    // public function store(Request $request)
    // {
    //     // Step 1: Validate request data
    //     $request->validate([
    //         'category' => 'required|string|max:255',
    //         'amount' => 'required|numeric|min:0',
    //         'expense_date' => 'required|date',
    //         'description' => 'nullable|string',
    //     ]);

    //     $user = Auth::user();

    //     // Step 2: Check if the category already exists for the user
    //     $category = $user->categories()->firstOrCreate(
    //         ['name' => $request->category]
    //     );

    //     // Step 3: Check if there's a budget set for this category
    //     $budget = $user->budgets()->where('category_id', $category->id)->first();

    //     if ($budget) {
    //         // Check if the budget amount is sufficient
    //         if ($request->amount > $budget->amount) {
    //             return response()->json(['error' => 'Insufficient budget for this category'], 400);
    //         }

    //         // Deduct the expense amount from the budget
    //         $budget->amount -= $request->amount;
    //         $budget->save();
    //     }

    //     // Step 4: Create the expense
    //     $expense = $user->expenses()->create([
    //         'category_id' => $category->id,
    //         'amount' => $request->amount,
    //         'expense_date' => $request->expense_date,
    //         'description' => $request->description,
    //     ]);

    //     return response()->json(['message' => 'Expense created successfully', 'expense' => $expense]);
    // }

    public function store(Request $request)
    {
        try {
            // Validate request data
            $request->validate([
                'category' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'expense_date' => 'required|date',
                'description' => 'nullable|string',
            ]);

            $user = Auth::user();

            // Find or create the category
            $category = $user->categories()->firstOrCreate(['name' => $request->category]);

            // Find the budget for this category, if it exists
            $budget = $user->budgets()->where('category_id', $category->id)->first();

            if ($budget) {
                // Check if expense exceeds remaining budget
                if ($request->amount > $budget->remaining) {
                    return response()->json(['error' => 'Insufficient budget for this category'], 400);
                }

                // Update budget's spent and remaining amounts
                $budget->spent += $request->amount;
                $budget->remaining = $budget->amount - $budget->spent;
                $budget->save();
            }

            // Create the expense
            $expense = $user->expenses()->create([
                'category_id' => $category->id,
                'amount' => $request->amount,
                'expense_date' => $request->expense_date,
                'description' => $request->description,
            ]);

            return response()->json(['message' => 'Expense created successfully', 'expense' => $expense], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors
            return response()->json(['error' => 'Validation error', 'details' => $e->validator->errors()], 422);
        } catch (\Exception $e) {
            // General errors
            Log::error('Failed to create expense', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create expense', 'details' => $e->getMessage()], 500);
        }
    }


    // Show a specific expense
    public function show($id)
    {
        $expense = Auth::user()->expenses()->with('category')->find($id);

        if (!$expense) {
            return response()->json(['message' => 'Expense not found'], 404);
        }

        return response()->json($expense);
    }


    // Update an existing expense


    public function update(Request $request, $id)
    {
        $expense = Auth::user()->expenses()->findOrFail($id);
        //  Validate the request data
        $validatedData = $request->only(['category', 'amount', 'expense_date', 'description']);

        //  Check if any data is available for update
        if (empty($validatedData)) {
            Log::error('No data provided in the request.');
            return response()->json(['error' => 'No data provided to update'], 422);
        }

        //  Attempt to update the expense and handle potential errors
        try {
            $updated = $expense->update($validatedData);

            // Check if the update was successful
            if ($updated) {
                // Reload the expense to get the latest data
                $updatedExpense = $expense->fresh();
                Log::info('Updated Expense Data:', $updatedExpense->toArray());

                return response()->json(['message' => 'Expense updated successfully', 'expense' => $updatedExpense]);
            } else {
                Log::error('Failed to update expense, no changes made.');
                return response()->json(['error' => 'Failed to update expense'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Exception occurred while updating expense:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update expense', 'details' => $e->getMessage()], 500);
        }
    }
    // analytics for expenses

    // public function analytics()
    // {
    //     try {
    //         $userId = Auth::id();

    //         // Fetch expenses grouped by category
    //         $categorizedExpenses = Expense::where('user_id', $userId)
    //             ->with('category')
    //             ->get()
    //             ->groupBy('category.name');

    //         // Prepare analytics data
    //         $analyticsData = $categorizedExpenses->map(function ($expenses, $categoryName) use ($userId) {
    //             $totalAmount = $expenses->sum('amount');
    //             $categoryId = $expenses->first()->category_id;

    //             // Fetch the budget for this category
    //             $budget = Budget::where('user_id', $userId)
    //                 ->where('category_id', $categoryId)
    //                 ->first();

    //             return [
    //                 'category' => $categoryName,
    //                 'total_amount' => $totalAmount,
    //                 'expense_count' => $expenses->count(),
    //                 'remaining_budget' => $budget->remaining ?? null,
    //                 'spent_budget' => $budget->spent ?? null,
    //                 'expenses' => $expenses,
    //             ];
    //         });

    //         // Calculate overall total expense amount
    //         $overallTotalExpense = $categorizedExpenses->flatten()->sum('amount');

    //         return response()->json([
    //             'data' => $analyticsData->values(),
    //             'overall_total_expense' => $overallTotalExpense,
    //         ], 200);

    //     } catch (\Exception $e) {
    //         // Log and handle general errors
    //         Log::error('Failed to fetch analytics data', ['error' => $e->getMessage()]);
    //         return response()->json(['error' => 'Failed to retrieve analytics data', 'details' => $e->getMessage()], 500);
    //     }
    // }

    public function analytics()
    {
        try {
            $userId = Auth::id();

            // Step 1: Fetch expenses grouped by category
            $categorizedExpenses = Expense::where('user_id', $userId)
                ->with('category')
                ->get()
                ->groupBy('category.name');

            // Step 2: Initialize cumulative totals
            $overallTotalExpense = 0;
            $overallTotalBudget = 0;
            $overallRemainingBudget = 0;
            $overallSpentBudget = 0;

            // Step 3: Prepare analytics data
            $analyticsData = $categorizedExpenses->map(function ($expenses, $categoryName) use ($userId, &$overallTotalExpense, &$overallTotalBudget, &$overallRemainingBudget, &$overallSpentBudget) {
                $totalAmount = $expenses->sum('amount');
                $categoryId = $expenses->first()->category_id;

                // Fetch the budget for this category
                $budget = Budget::where('user_id', $userId)
                    ->where('category_id', $categoryId)
                    ->first();

                // Update cumulative totals
                $overallTotalExpense += $totalAmount;
                $overallTotalBudget += $budget->amount ?? 0;
                $overallRemainingBudget += $budget->remaining ?? 0;
                $overallSpentBudget += $budget->spent ?? 0;

                return [
                    'category' => $categoryName,
                    'total_amount' => $totalAmount,
                    'expense_count' => $expenses->count(),
                    'remaining_budget' => $budget->remaining ?? null,
                    'spent_budget' => $budget->spent ?? null,
                    'budget_amount' => $budget->amount ?? null, // Include the total budget amount for this category
                    'expenses' => $expenses, // Array of individual expenses in this category
                ];
            });

            return response()->json([
                'data' => $analyticsData->values(),
                'overall_total_expense' => $overallTotalExpense,
                'overall_total_budget' => $overallTotalBudget,
                'overall_remaining_budget' => $overallRemainingBudget,
                'overall_spent_budget' => $overallSpentBudget,
            ], 200);
        } catch (\Exception $e) {
            // Log and handle general errors
            Log::error('Failed to fetch analytics data', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to retrieve analytics data', 'details' => $e->getMessage()], 500);
        }
    }



    // Delete an expense
    public function destroy($id)
    {
        $expense = Auth::user()->expenses()->findOrFail($id);
        if (!$expense) {
            return response()->json(['message' => 'Expense not found'], 404);
        } else {
            $expense->delete();
            return response()->json(['message' => 'Expense deleted successfully']);
        }
    }
}
