<?php

namespace StellarWP\PluginFramework\Support;

use StellarWP\PluginFramework\Contracts\UrlRetrievalStrategy;
use WP_Term;

class TaxonomyUrlRetrieval implements UrlRetrievalStrategy
{
    /**
     * Should return true if the item is publicly queryable, false otherwise.
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function isPublic($identifier)
    {
        $taxonomy = get_taxonomy($identifier);

        return $taxonomy && $taxonomy->publicly_queryable;
    }

    /**
     * Gets items according to strategy logic and returns an array of items ids.
     *
     * @param string               $identifier
     * @param int                  $number
     * @param array<string, mixed> $params
     *
     * @return int[]
     */
    public function getItems($identifier, $number, $params)
    {
        $args = [
            'taxonomy'   => $identifier,
            'number'     => $number,
            'hide_empty' => true,
            'orderby'    => 'date',
            'order'      => 'ASC',
            'fields'     => 'ids',
            'meta_query' => [],
        ];

        if ($params) {
            $args = wp_parse_args($params, $args);
        }

        $terms = get_terms($args);

        return ! $terms || is_wp_error($terms) || is_string($terms) ? [] : array_map(
            function ($item) {
                return $item instanceof WP_Term ? $item->term_id : intval($item);
            },
            $terms
        );
    }

    /**
     * Retrieves item link by its id.
     *
     * @param int    $id
     * @param string $identifier
     *
     * @return string
     */
    public function getLink($id, $identifier)
    {
        $link = get_term_link($id, $identifier);

        return is_wp_error($link) ? '' : $link;
    }
}
