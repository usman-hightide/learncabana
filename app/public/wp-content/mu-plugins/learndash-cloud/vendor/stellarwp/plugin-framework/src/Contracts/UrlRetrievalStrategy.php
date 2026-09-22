<?php

namespace StellarWP\PluginFramework\Contracts;

interface UrlRetrievalStrategy
{
    /**
     * Should return true if the item is publicly queryable, false otherwise.
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function isPublic($identifier);

    /**
     * Gets items according to strategy logic and returns an array of items ids.
     *
     * @param string               $identifier
     * @param int                  $number
     * @param array<string, mixed> $params
     *
     * @return int[]
     */
    public function getItems($identifier, $number, $params);

    /**
     * Retrieves item link by its id.
     *
     * @param int    $id
     * @param string $identifier
     *
     * @return string
     */
    public function getLink($id, $identifier);
}
