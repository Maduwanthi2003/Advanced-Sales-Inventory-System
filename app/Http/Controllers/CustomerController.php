<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // List customers with optional search and pagination.
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $baseQuery = Customer::query();
        $customers = (clone $baseQuery)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        if ($search !== '') {
            $customers->getCollection()->each(function ($customer) use ($baseQuery) {
                $customer->setAttribute(
                    'list_serial',
                    (clone $baseQuery)->where('id', '<', $customer->id)->count() + 1
                );
            });
        }

        return view('customers.index', compact('customers', 'search'));
    }

    // Show the customer creation form.
    public function create()
    {
        return view('customers.create');
    }

    // Validate and save a new customer.
    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        Customer::create($data);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    // Show the customer edit form.
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    // Validate and save changes to a customer.
    public function update(Request $request, Customer $customer)
    {
        $data = $this->validatedData($request);

        $customer->update($data);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    // Delete a customer when no invoices depend on it.
    public function destroy(Customer $customer)
    {
        if ($customer->invoices()->exists()) {
            return redirect()
                ->route('customers.index')
                ->with('error', 'Cannot delete a customer with existing invoices.');
        }

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    // Normalize and validate customer form data.
    private function validatedData(Request $request): array
    {
        if ($request->has('phone')) {
            $request->merge([
                'phone' => trim((string) $request->input('phone', '')),
            ]);
        }

        return $request->validate(
            [
                'name' => ['required', 'string', 'max:150'],
                'contact_person' => ['required', 'string', 'max:150'],
                'phone' => ['required', 'string', 'max:16', 'regex:/^\+?[0-9]{9,15}$/'],
                'email' => ['required', 'email', 'max:255'],
                'is_active' => ['required', 'boolean'],
                'address' => ['nullable', 'string', 'max:2000'],
                'city' => ['nullable', 'string', 'max:120'],
                'postal_code' => ['nullable', 'string', 'max:30'],
                'country' => ['nullable', 'string', 'max:120'],
                'notes' => ['nullable', 'string', 'max:5000'],
            ],
            [
                'phone.regex' => 'The phone number must contain 9 to 15 digits and may start with a single leading + (e.g., 0771234567 or +94771234567).',
            ]
        );
    }
}
