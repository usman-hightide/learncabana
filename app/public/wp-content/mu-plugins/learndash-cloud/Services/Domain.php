<?php

namespace StellarWP\LearnDashCloud\Services;

use StellarWP\PluginFramework\Services\Domain as FrameworkDomain;

class Domain extends FrameworkDomain
{
    public const NEXCESS_NAMESERVERS = [
        'ns1.nexcess.net',
        'ns2.nexcess.net',
        'ns3.nexcess.net',
        'ns4.nexcess.net',
    ];

    /**
     * A cache of the current site's IP address.
     *
     * @var ?string
     */
    private $currentIpAddress;

    /**
     * Overrides the framework Domain service method that doesn't check for the vanity domain.
     *
     * Determine if the given hostname has valid A or AAAA records that point to this site.
     *
     * @param string $hostname The hostname to inspect.
     *
     * @return bool True if at least one valid record exists, false otherwise.
     */
    public function hasValidDnsRecords($hostname)
    {
        $records = $this->getDnsRecords($hostname, DNS_A | DNS_AAAA);

        foreach ($records as $record) {
            if (empty($record['type'])) {
                continue;
            }

            if ('A' === $record['type'] && ! empty($record['ip'])) {
                if ($this->getCurrentIpAddress() === $record['ip']) {
                    return true;
                }
            }
        }

        // If we haven't returned yet, we don't have a match.
        return false;
    }

    /**
     * Get the IP address for the current site.
     *
     * @return ?string Either the IP address for the site null if it could not be determined.
     */
    public function getCurrentIpAddress()
    {
        if (! isset($this->currentIpAddress)) {
            $ip = gethostbyname($this->settings->temp_domain);

            $this->currentIpAddress = $ip ? $ip : null;
        }

        return $this->currentIpAddress;
    }
}
