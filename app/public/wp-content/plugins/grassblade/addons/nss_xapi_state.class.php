<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class NSS_XAPI_STATE extends NSS_XAPI {

	public $state_url;
	function __construct($endpoint = null, $user = null, $pass = null, $version = "1.0.0") {
		parent::__construct($endpoint, $user, $pass, $version);
		if(!empty($endpoint))
		{
			$this->endpoint = $endpoint;
			$this->state_url = $endpoint."activities/state";
		}
	}

	function SendState($activityId, $agent, $stateId, $data, $registration = null) {
		grassblade_debug("sendstate called");
		if(empty($activityId) || empty($agent) || empty($stateId))
		{
			grassblade_debug("[NSS_XAPI_STATE::SendState] Empty Values: ".empty($activityId).'||'.empty($agent).'||'.empty($stateId));
			return "";
		}

		$url = $this->state_url."?stateId=".$stateId."&activityId=".rawurlencode($activityId)."&agent=".rawurlencode(json_encode($agent));
		if(!empty($registration))
		$url .= "&registration=".$registration;

		grassblade_debug("[NSS_XAPI_STATE::SendState] URL: ".$url);

		return $this->PutCurl($url, $data);
	}
	function GetState($activityId, $agent, $stateId, $registration = null) {

		if(empty($activityId) || empty($agent) || empty($stateId))
		{
			grassblade_debug("[NSS_XAPI_STATE::GetState] Empty Values: ".empty($activityId).'||'.empty($agent).'||'.empty($stateId));
			return;
		}

		$url = $this->state_url."?stateId=".$stateId."&activityId=".rawurlencode($activityId)."&agent=".rawurlencode(json_encode($agent));
		if(!empty($registration))
		$url .= "&registration=".$registration;

		grassblade_debug("[NSS_XAPI_STATE::GetState] URL: ".$url);
		return $this->GetCurl($url);
	}
}
