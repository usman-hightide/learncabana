<?php
/**
 * Single source of truth for login-attempts constants. Avoids drift
 * between Intercept, AttemptResolver, and the REST endpoint.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\LoginAttempts;

/**
 * Shared constants for login-attempt handling.
 *
 * @internal
 */
final class Constants {

	/** Customer-side query param appended to the failure redirect. */
	const QUERY_PARAM = 'tl_attempt';

	/** Param name we forward on the canonical activity-page route. */
	const CANONICAL_PARAM = 'attempt';

	/**
	 * Upstream emits `lpat_<UUID>`; this regex pins to exactly that
	 * shape. Anything else is silently stripped (likely a stale
	 * bookmark, not an attack).
	 */
	const ATTEMPT_ID_REGEX = '/^lpat_[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

	/**
	 * Bounds on the list endpoint's per_page. The SaaS enforces its
	 * own clamp; these bounds are defense-in-depth so a hostile
	 * client can't drag a huge page through the Connector even if
	 * the SaaS clamp ever breaks.
	 */
	const MIN_PER_PAGE     = 1;
	const MAX_PER_PAGE     = 200;
	const DEFAULT_PER_PAGE = 50;

	// REST status codes used by the endpoint. Named so failure modes are
	// self-documenting in error responses.
	const STATUS_BAD_REQUEST       = 400;
	const STATUS_FORBIDDEN         = 403;
	const STATUS_NOT_FOUND         = 404;
	const STATUS_TOO_MANY_REQUESTS = 429;
	const STATUS_BAD_GATEWAY       = 502;
}
