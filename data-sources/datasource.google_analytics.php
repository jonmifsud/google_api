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
    public $dsParamPROFILEID = '97615465';
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

    public function execute(&$param_pool)
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