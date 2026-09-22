<?php

namespace StellarWP\PluginFramework\Support;

use StellarWP\PluginFramework\Contracts\UrlRetrievalStrategy;

class UrlRetrievalContext
{
    /**
     * @var UrlRetrievalStrategy
     */
    protected $strategy;

    /**
     * @param UrlRetrievalStrategy $strategy
     *
     * @return void
     */
    public function setStrategy($strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * @param string $description
     * @param int    $id
     *
     * @return string
     */
    protected function getDescription($description, $id = 0)
    {
        return false !== mb_strpos($description, '%d') && $id ? sprintf($description, $id) : $description;
    }

    /**
     * @param string               $identifier
     * @param int                  $number
     * @param string               $description
     * @param array<string, mixed> $params
     *
     * @return VisualRegressionUrl[]
     */
    public function getUrls($identifier, $number, $description, $params = [])
    {
        $urls = [];

        if (! $this->strategy->isPublic($identifier)) {
            return $urls;
        }

        $items = $this->strategy->getItems($identifier, $number, $params);

        if (! $items) {
            return $urls;
        }

        foreach ($items as $id) {
            $link = $this->strategy->getLink($id, $identifier);
            if (! $link) {
                continue;
            }
            $urls[] = new VisualRegressionUrl($link, $this->getDescription($description, $id));
        }

        return $urls;
    }
}
