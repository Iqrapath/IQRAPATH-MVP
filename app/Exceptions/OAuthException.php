<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class OAuthException extends Exception
{
    /**
     * Create a new OAuth exception instance.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function render($request)
    {
        return redirect()->route('login')
            ->withErrors(['oauth' => $this->getMessage()]);
    }
}
