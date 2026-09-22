<?php

namespace StellarWP\PluginFramework\Support;

use StellarWP\PluginFramework\Contracts\UrlRetrievalStrategy;
use WP_Post;

class PostUrlRetrieval implements UrlRetrievalStrategy
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
        return 'product' === $identifier ? true : is_post_type_viewable($identifier);
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
            'post_type'      => $identifier,
            'posts_per_page' => $number,
            'orderby'        => 'date',
            'order'          => 'ASC',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'meta_query'     => []
        ];

        if ($params) {
            $args = wp_parse_args($params, $args);
        }

        return array_map(
            function ($item) {
                return $item instanceof WP_Post ? $item->ID : intval($item);
            },
            get_posts($args)
        );
    }

    /**
     * Retrieves post link by its id.
     *
     * @param int    $id
     * @param string $identifier
     *
     * @return string
     */
    public function getLink($id, $identifier)
    {
        return (string) get_permalink($id);
    }
}
