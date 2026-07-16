@extends('admin.layout')

@section('title', 'Admin Accounts')
@section('page-title', 'Admin Accounts')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <p class="text-sm text-maroon-500 max-w-lg">Only Super Admins can see this page. Super Admins can create and manage other admin accounts; regular admins can't.</p>
        <a href="{{ route('admin.admins.create') }}" class="btn-gold">+ Add Admin</a>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gold-200/60 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-maroon-400 border-b border-gold-100">
                    <th class="px-5 py-2.5 font-medium">Name</th>
                    <th class="px-5 py-2.5 font-medium">Username</th>
                    <th class="px-5 py-2.5 font-medium">Role</th>
                    <th class="px-5 py-2.5 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($admins as $admin)
                    <tr class="border-b border-gold-50 last:border-0 hover:bg-cream/50 transition">
                        <td class="px-5 py-3 text-maroon-800 font-semibold">
                            {{ $admin->name }}
                            @if ($admin->id === auth('admin')->id())
                                <span class="text-xs font-normal text-maroon-400">(you)</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-maroon-600">{{ $admin->username }}</td>
                        <td class="px-5 py-3">
                            @if ($admin->is_super_admin)
                                <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full border bg-gold-100 text-gold-700 border-gold-300/60">🛡️ Super Admin</span>
                            @else
                                <span class="inline-block text-xs font-medium px-2.5 py-1 rounded-full border bg-cream text-maroon-500 border-gold-200/60">Admin</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right space-x-3">
                            <a href="{{ route('admin.admins.edit', $admin) }}" class="text-gold-600 hover:text-gold-700 font-medium">Edit</a>
                            @if ($admin->id !== auth('admin')->id())
                                <form action="{{ route('admin.admins.destroy', $admin) }}" method="POST" class="inline" onsubmit="return confirm('Delete admin account {{ $admin->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-600 font-medium">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
