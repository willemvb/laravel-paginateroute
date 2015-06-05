<?php

namespace Spatie\PaginateRoute;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Translation\Translator;

class PaginateRoute
{
    /**
     * @param  \Illuminate\Translation\Translator $translator
     * @param  \Illuminate\Routing\Router $router
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Contracts\Routing\UrlGenerator $urlGenerator
     */
    public function __construct(Translator $translator, Router $router, Request $request, UrlGenerator $urlGenerator)
    {
    $this->translator                     = $translator;
    $this->router                         = $router;
    $this->request                        = $request;
    $this->urlGenerator                   = $urlGenerator;

        // Unfortunately we can't do this in the service provider since routes are booted first
        $this->translator->addNamespace('paginateroute', __DIR__.'/../resources/lang');

        $this->pageName = $this->translator->get('paginateroute::paginateroute.page');
    }

    /**
     * Register the Route::paginate macro
     * 
     * @return void
     */
    public function registerMacros()
    {
        $pageName = $this->pageName;

        $this->router->macro('paginate', function ($uri, $action) use ($pageName) {
            // $this is the router
            $this->group(
                ['middleware' => 'Spatie\PaginateRoute\SetPageMiddleware'],
                function () use ($pageName, $uri, $action) {
                    // $this is the router
                    $this->get($uri, $action);
                    $this->get($uri.'/'.$pageName.'/{page}', $action)->where('page', '[0-9]+');
                });
        });
    }

    /**
     * Get the next page number
     * 
     * @param  \Illuminate\Contracts\Pagination\Paginator $paginator
     * @return string|null
     */
    public function nextPage(Paginator $paginator)
    {
        if (!$paginator->hasMorePages()) {
            return null;
        }

        return $this->router->getCurrentRoute()->parameter('page') + 1;
    }

    /**
     * Determine wether there is a next page
     * 
     * @param  \Illuminate\Contracts\Pagination\Paginator $paginator
     * @return bool
     */
    public function hasNextPage(Paginator $paginator)
    {
        return $this->nextPage($paginator) !== null;
    }

    /**
     * Get the next page url
     * 
     * @param  \Illuminate\Contracts\Pagination\Paginator $paginator
     * @return string|null
     */
    public function nextPageUrl(Paginator $paginator)
    {
        $nextPage = $this->nextPage($paginator);

        if ($nextPage === null) {
            return $nextPage;
        }

        $url = str_replace('{page}', $nextPage, $this->router->getCurrentRoute()->getUri());

        return $this->urlGenerator->to($url);
    }

    /**
     * Get the previous page number
     * 
     * @return string|null
     */
    public function previousPage()
    {
        if ($this->router->getCurrentRoute()->parameter('page') <= 1) {
            return null;
        }

        return $this->router->getCurrentRoute()->parameter('page') - 1;
    }

    /**
     * Determine wether there is a previous page
     * 
     * @return bool
     */
    public function hasPreviousPage()
    {
        return $this->previousPage() !== null;
    }

    /**
     * Get the previous page url
     * 
     * @param  bool $full  Return the full version of the url in for the first page
     *                     Ex. /users/page/1 instead of /users
     * @return string|null
     */
    public function previousPageUrl($full = false)
    {
        $previousPage = $this->previousPage();

        if ($previousPage === null) {
            return null;
        }

        if ($previousPage === 1 && !$full) {
            $url = str_replace($this->pageName.'/{page}', '', $this->router->getCurrentRoute()->getUri());

            return $this->urlGenerator->to($url);
        }

        $url = str_replace('{page}', $previousPage, $this->router->getCurrentRoute()->getUri());

        return $this->urlGenerator->to($url);
    }
}
