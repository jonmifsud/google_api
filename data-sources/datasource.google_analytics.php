<?php

require_once TOOLKIT . '/class.datasource.php';

class Google_AnalyticsDatasource extends DataSource
{
    public $dsParamROOTELEMENT = 'google-analytics';
    public $dsParamSTARTPAGE = '1';
    public $dsParamLIMIT = '20';
    public $dsParamSORT = 'pageviews';
    public $dsParamORDER = 'desc';
    public $dsParamSOURCE = 0;
    public $dsParamPROFILEID = '14710162';
    public $dsParamSTARTDATE = '-7 day';
    public $dsParamENDDATE = 'today';


    public $dsParamDIMENSIONS = array(
            'pagePath',
            'dimension1'
        );
    public $dsParamMETRICS = array(
            'pageviews'
        );
    public $dsParamFILTERS = array(
            'dimension1' => 'Article'
        );

    public function about()
    {
        return array(
            'name' => 'Google Analytics'
        );
    }

    public function getSource()
    {
        return $this->dsParamSOURCE;
    }

    public function allowEditorToParse()
    {
        return false;
    }

    /**
     * Given either the Datasource object or an array of settings for a
     * Remote Datasource, this function will return it's cache ID, which
     * is stored in tbl_cache.
     *
     * @since 1.1
     * @param array|object $settings
     */
    public static function buildCacheID($settings)
    {
        $cache_id = null;

        $paramstring = '';
        foreach($settings as $key => $value) {
           if (strpos($key,'dsParam') === 0){
                $paramstring .= $value;
           }
        }
        $cache_id = md5($paramstring);

        return $cache_id;
    }
    /**
     * Helper function to build Cache information block
     *
     * @param XMLElement $wrapper
     * @param Cacheable $cache
     * @param string $cache_id
     */
    public static function buildCacheInformation(XMLElement $wrapper, Cacheable $cache, $cache_id)
    {
        $cachedData = $cache->read($cache_id);
        if (is_array($cachedData) && !empty($cachedData) && (time() < $cachedData['expiry'])) {
            $a = Widget::Anchor(__('Clear now'), SYMPHONY_URL . getCurrentPage() . 'clear_cache/');
            $wrapper->appendChild(
                new XMLElement('p', __('Cache expires in %d minutes. %s', array(
                    ($cachedData['expiry'] - time()) / 60,
                    $a->generate(false)
                )), array('class' => 'help'))
            );
        } else {
            $wrapper->appendChild(
                new XMLElement('p', __('Cache has expired or does not exist.'), array('class' => 'help'))
            );
        }
    }

    public function execute(&$param_pool){
        $result = new XMLElement($this->dsParamROOTELEMENT);

        try {
            // Check for an existing Cache for this Datasource
            $cache_id = self::buildCacheID($this);
            $cache = Symphony::ExtensionManager()->getCacheProvider('google_api');
            $cachedData = $cache->read($cache_id);
            $writeToCache = null;
            $isCacheValid = true;
            $creation = DateTimeObj::get('c');
            // Execute if the cache doesn't exist, or if it is old.
            if (
                (!is_array($cachedData) || empty($cachedData)) // There's no cache.
                || (time() - $cachedData['creation']) > ($this->dsParamCACHE * 60) // The cache is old.
            ) {
                if (Mutex::acquire($cache_id, $this->dsParamTIMEOUT, TMP)) {
                    $result = self::getResults($param_pool);
                    Mutex::release($cache_id, TMP);
                }
            } else {
                $data = trim($cachedData['data']);
                $creation = DateTimeObj::get('c', $cachedData['creation']);
            }

            if (is_array($cachedData) && !empty($cachedData) && $writeToCache === false) {
                $data = trim($cachedData['data']);
                $isCacheValid = false;
                $creation = DateTimeObj::get('c', $cachedData['creation']);
                if (empty($data)) {
                    $this->_force_empty_result = true;
                }
            }

            $result->setAttribute('status', ($isCacheValid === true ? 'fresh' : 'stale'));
            $result->setAttribute('cache-id', $cache_id);
            $result->setAttribute('creation', $creation);
        } catch (Exception $e) {
            $result->appendChild(new XMLElement('error', $e->getMessage()));
        }
        if ($this->_force_empty_result) {
            $result = $this->emptyXMLSet();
        }

    }


    public function getResults(&$param_pool)
    {

        $client = ExtensionManager::create('google_api')->getClient();
        $analytics = new Google_Service_Analytics($client);

        ########## Google analytics Settings.. #############
        $google_analytics_profile_id    = 'ga:' . $this->dsParamPROFILEID;
        $google_analytics_dimensions    = 'ga:' . implode(',ga:',$this->dsParamDIMENSIONS);
        $google_analytics_metrics       = 'ga:' . implode(',ga:',$this->dsParamMETRICS);
        $google_analytics_sort_by       = ( $this->dsParamORDER == 'desc' ? '-' : '' ) . 'ga:' . $this->dsParamSORT;
        $google_analytics_max_results   = $this->dsParamLIMIT;
        $google_analytics_filters   = '';

        foreach ($this->dsParamFILTERS as $key => $value) {
            if (!empty($google_analytics_filters)) $google_analytics_filters.= ',';
            $google_analytics_filters.= 'ga:' . $key . '==' . $value;
        }

        //set start date to previous month
        $start_date = date("Y-m-d", strtotime($this->dsParamSTARTDATE) ); 
        
        //end date as today
        $end_date = date("Y-m-d", strtotime($this->dsParamENDDATE) ); 

        //analytics parameters (check configuration file)
        $params = array(
            'dimensions' => $google_analytics_dimensions,
            'sort' => $google_analytics_sort_by,
            'filters' => $google_analytics_filters,
            'max-results' => $google_analytics_max_results
        );
        
        //get results from google analytics
        $results = $analytics->data_ga->get($google_analytics_profile_id,$start_date,$end_date, $google_analytics_metrics, $params);

        $result = new XMLElement($this->dsParamROOTELEMENT);

        $keys = array_merge($this->dsParamDIMENSIONS,$this->dsParamMETRICS);

        foreach ($results->rows as $row) {
            # code...
            $entry = new XMLElement('entry');
            foreach ($row as $key => $value) {
                $entry->appendChild( new XMLElement( $keys[$key], $value ) );
                if ($keys[$key] == 'pagePath'){
                    $entryID = $this->getEntryID($value);
                    $entry->setAttribute('id',$entryID);
                    $param_pool['ds-' . $this->dsParamROOTELEMENT .'.entry-id'][] = $entryID;
                }
            }
            $result->appendChild($entry);
        }

        return $result;
    }

    private function getEntryID($url){
        preg_match( "/\/([0-9]+)\//", $url, $matches);

        return $matches[1];
    }
}