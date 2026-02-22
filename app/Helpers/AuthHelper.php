<?php

if (!function_exists('user_context')) {
    /**
     * Get the current frozen UserContext from session.
     * 
     * @return \App\DTO\Auth\UserContext|null
     */
    function user_context(): ?\App\DTO\Auth\UserContext
    {
        return app(\App\Services\Auth\UserContextResolver::class)->getFromSession();
    }
}
