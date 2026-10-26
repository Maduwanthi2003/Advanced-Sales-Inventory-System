<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    // List suppliers with optional search and pagination.
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $baseQuery = Supplier::query();
        $suppliers = (clone $baseQuery)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        if ($search !== '') {
            $suppliers->getCollection()->each(function ($supplier) use ($baseQuery) {
                $supplier->setAttribute(
                    'list_serial',
                    (clone $baseQuery)->where('id', '<', $supplier->id)->count() + 1
                );
            });
        }

        return view('suppliers.index', compact('suppliers', 'search'));
    }

    // Show the supplier creation form.
    public function create()
    {
        return view('suppliers.create');
    }

    // Validate and save a new supplier.
    public function store(Request $request)
    {
        $data = $this->validateSupplier($request);

        Supplier::create($data);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier added successfully.');
    }

    // Show the supplier edit form.
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    // Validate and save changes to a supplier.
    public function update(Request $request, Supplier $supplier)
    {
        $data = $this->validateSupplier($request);

        $supplier->update($data);

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    // Delete a supplier when no products or purchases depend on it.
    public function destroy(Supplier $supplier)
    {
        if (
            $supplier->products()->exists() ||
            $supplier->purchases()->exists()
        ) {
            return redirect()
                ->route('suppliers.index')
                ->with(
                    'error',
                    'Cannot delete supplier with existing products or purchases.'
                );
        }

        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }

    // Normalize and validate supplier form data.
    private function validateSupplier(Request $request): array
    {
        if ($request->has('phone')) {
            $request->merge([
                'phone' => trim((string) $request->input('phone', '')),
            ]);
        }

        return $request->validate(
            [
                'name' => ['required', 'string', 'max:150'],
                'company_name' => ['required', 'string', 'max:150'],
                'contact_person' => ['required', 'string', 'max:150'],
                'phone' => ['required', 'string', 'max:16', 'regex:/^\+?[0-9]{9,15}$/'],
                'email' => ['required', 'email', 'max:255'],
                'address' => ['nullable', 'string', 'max:2000'],
                'city' => ['nullable', 'string', 'max:120'],
                'postal_code' => ['nullable', 'string', 'max:30'],
                'country' => ['nullable', 'string', 'max:120'],
                'notes' => ['nullable', 'string', 'max:5000'],
                'is_active' => ['required', Rule::in(['0', '1'])],
            ],
            [
                'phone.regex' => 'The phone number must contain 9 to 15 digits and may start with a single leading + (e.g., 0771234567 or +94771234567).',
            ]
        );
    }
}
