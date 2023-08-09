<?php

namespace Laravel\Folio;

use Illuminate\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Illuminate\Routing\UrlGenerator;

class UrlGeneratorDecorator extends UrlGenerator implements UrlGeneratorContract
{
    /**
     * Creates a new url generator decorator instance.
     */
    public function __construct(
        protected UrlGenerator $urlGenerator,
        protected FolioRoutes $folioRoutes,
    ) {
        parent::__construct(
            $urlGenerator->routes,
            $urlGenerator->request,
            $urlGenerator->assetRoot,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->urlGenerator->current();
    }

    /**
     * {@inheritDoc}
     */
    public function previous($fallback = false)
    {
        return $this->urlGenerator->previous($fallback);
    }

    /**
     * {@inheritDoc}
     */
    public function to($path, $extra = [], $secure = null)
    {
        return $this->urlGenerator->to($path, $extra, $secure);
    }

    /**
     * {@inheritDoc}
     */
    public function secure($path, $parameters = [])
    {
        return $this->urlGenerator->secure($path, $parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function asset($path, $secure = null)
    {
        return $this->urlGenerator->asset($path, $secure);
    }

    /**
     * {@inheritDoc}
     */
    public function route($name, $parameters = [], $absolute = true)
    {
        if (! is_null($route = $this->routes->getByName($name))) {
            return $this->urlGenerator->toRoute($route, $parameters, $absolute);
        }

        if ($this->folioRoutes->has($name)) {
            return $this->folioRoutes->get($name, $parameters, $absolute);
        }

        return $this->urlGenerator->route($name, $parameters, $absolute);
    }

    /**
     * {@inheritDoc}
     */
    public function action($action, $parameters = [], $absolute = true)
    {
        return $this->urlGenerator->action($action, $parameters, $absolute);
    }

    /**
     * {@inheritDoc}
     */
    public function getRootControllerNamespace()
    {
        return $this->urlGenerator->getRootControllerNamespace();
    }

    /**
     * {@inheritDoc}
     */
    public function setRootControllerNamespace($rootNamespace)
    {
        return $this->urlGenerator->setRootControllerNamespace($rootNamespace);
    }
}
