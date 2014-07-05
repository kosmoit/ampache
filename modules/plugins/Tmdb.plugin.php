<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

class AmpacheTmdb {

    public $name           = 'Tmdb';
    public $description    = 'Tmdb metadata integration';
    public $version        = '000001';
    public $min_ampache    = '370009';
    public $max_ampache    = '999999';
    
    // These are internal settings used by this class, run this->load to
    // fill them out
    private $api_key;

    /**
     * Constructor
     * This function does nothing
     */
    public function __construct() {
        return true;
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install() {
        
        if (Preference::exists('tmdb_api_url')) { return false; }

        Preference::insert('tmdb_api_key','Tmdb api key','','75','string','plugins');
        
        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall() {
    
        Preference::delete('tmdb_api_url');
        
        return true;
    } // uninstall

    /**
     * load
     * This is a required plugin function; here it populates the prefs we 
     * need for this object.
     */
    public function load($user) {
        
        $user->set_preferences();
        $data = $user->prefs;

        if (strlen(trim($data['tmdb_api_key']))) {
            $this->api_key = trim($data['tmdb_api_key']);
        }
        else {
            debug_event($this->name,'No Tmdb api key, metadata plugin skipped','3');
            return false;
        }
        
        return true;
    } // load

    /**
     * get_metadata
     * Returns song metadata for what we're passed in.
     */
    public function get_metadata($gather_types, $media_info) {
        debug_event('tmdb', 'Getting metadata from Tmdb...', '5');

        // TVShow / Movie metadata only
        if (!in_array('tvshow', $gather_types) && !in_array('movie', $gather_types)) {
            debug_event('tmdb', 'Not a valid media type, skipped.', '5');
            return null;
        }
        $token = new \Tmdb\ApiToken($this->api_key);
        $client = new \Tmdb\Client($token);
        
        $title = $media_info['original_name'] ?: $media_info['title'];
        
        $results = array();
        try {
            if (in_array('movie', $gather_types)) {
                if (!empty($title)) {
                    $apires = $client->getSearchApi()->searchMovies($title);
                    if (count($apires['results']) > 0) {
                        $release = $apires['results'][0];
                        $results['tmdb_id'] = $release['id'];
                        $results['original_name'] = $release['original_title'];
                        if (!empty($release['release_date'])) {
                            $results['release_date'] = strtotime($release['release_date']);
                            $results['year'] = date("Y", $results['release_date']);  // Production year shouldn't be the release date
                        }
                    }
                }
            }
            
            if (in_array('tvshow', $gather_types)) {
                if (!empty($media_info['tvshow'])) {
                    $apires = $client->getSearchApi()->searchTv($media_info['tvshow']);
                    if (count($apires['results']) > 0) {
                        // Get first match
                        $release = $apires['results'][0];
                        $results['tmdb_tvshow_id'] = $release['id'];
                        $results['tvshow'] = $release['original_name'];
                        if (!empty($release['first_air_date'])) {
                            $results['tvshow_year'] = date("Y", strtotime($release['first_air_date']));
                        }
                        
                        if ($media_info['tvshow_season'] && $media_info['tvshow_episode']) {
                            $release = $client->getTvEpisodeApi()->getEpisode($results['tmdb_tvshow_id'], $media_info['tvshow_season'], $media_info['tvshow_episode']);
                            if ($release['id']) {
                                $results['tmdb_id'] = $release['id'];
                                $results['tvshow_season'] = $release['season_number'];
                                $results['tvshow_episode'] = $release['episode_number'];
                                $results['original_name'] = $release['name'];
                                if (!empty($release['air_date'])) {
                                    $results['release_date'] = strtotime($release['release_date']);
                                    $results['year'] = date("Y", $results['release_date']);
                                }
                                $results['description'] = $release['overview'];
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            debug_event('tmdb', 'Error getting metadata: ' . $e->getMessage(), '1');
        }
        
        return $results;
    } // get_metadata

} // end AmpacheTmdb
?>
