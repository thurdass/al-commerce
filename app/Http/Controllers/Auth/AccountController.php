<?php

namespace App\Http\Controllers\Auth;

use App\Actions\DeleteAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DeleteAccountRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function confirmDelete(Request $request): View
    {
        return view('auth.delete-account', ['hasStore' => $request->user()->store()->exists()]);
    }

    public function destroy(DeleteAccountRequest $request, DeleteAccount $deleteAccount): RedirectResponse
    {
        $deleteAccount->handle($request->user());

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home')->with('status', 'Sua conta foi excluída permanentemente.');
    }
}
