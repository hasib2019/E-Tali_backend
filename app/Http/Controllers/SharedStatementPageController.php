<?php

namespace App\Http\Controllers;

use App\Models\SharedStatement;
use Illuminate\View\View;

/** Public read-only statement page behind a shared link. */
class SharedStatementPageController extends Controller
{
    public function __invoke(string $token): View
    {
        $statement = SharedStatement::where('token', $token)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $statement->increment('views');

        return view('statement', ['s' => $statement, 'p' => $statement->payload]);
    }
}
