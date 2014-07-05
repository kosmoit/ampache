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

class Clip extends Video
{
    public $artist;
    public $song;
    public $video;

    public $f_artist;
    public $f_song;

    /**
     * Constructor
     * This pulls the clip information from the database and returns
     * a constructed object
     */
    public function __construct($id)
    {
        parent::__construct($id);

        $info = $this->get_info($id);
        foreach ($info as $key=>$value) {
            $this->$key = $value;
        }

        return true;

    } // Constructor

    /**
     * gc
     *
     * This cleans out unused clips
     */
    public static function gc()
    {
        $sql = "DELETE FROM `clip` USING `clip` LEFT JOIN `video` ON `video`.`id` = `clip`.`id` " .
            "WHERE `video`.`id` IS NULL";
        Dba::write($sql);
    }

    /**
     * create
     * This takes a key'd array of data as input and inserts a new clip entry, it returns the record id
     */
    public static function insert($data)
    {
        $sql = "INSERT INTO `clip` (`id`,`artist`,`song`) " .
            "VALUES (?, ?, ?)";
        Dba::write($sql, array($data['id'], $data['artist'], $data['song']));

        return $data['id'];

    } // create

    /**
     * update
     * This takes a key'd array of data as input and updates a clip entry
     */
    public static function update($data)
    {
        $sql = "UPDATE `clip` SET `artist` = ?, `song` = ? WHERE `id` = ?";
        Dba::write($sql, array($data['artist'], $data['song'], $data['id']));

        return true;

    } // update

    /**
     * format
     * this function takes the object and reformats some values
     */

    public function format()
    {
        parent::format();

        if ($this->artist) {
            $artist = new Artist($this->artist);
            $artist->format();
            $this->f_artist = $artist->f_link;
        }

        if ($this->song) {
            $song = new Song($this->song);
            $song->format();
            $this->f_song = $song->f_link;
        }

        return true;

    } //format

} // Clip class
