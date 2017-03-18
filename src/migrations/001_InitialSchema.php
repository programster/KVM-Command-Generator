<?php

/*
 * This is an example migration script. Please overwrite the up and down methods.
 */

class InitialSchema implements iRAP\Migrations\MigrationInterface
{
    public function up(\mysqli $mysqliConn) 
    {
        $queries[] = 
            "CREATE TABLE `guest` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        $queries[] = 
            "CREATE TABLE `disk` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `path` text NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        # table for mapping which disk(s) a guest uses.
        $queries[] = 
            "CREATE TABLE `guest_disk` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `guest_id` int unsigned NOT NULL,
                `disk_id` int unsigned NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE INDEX (guest_id, disk_id),
                FOREIGN KEY (guest_id) REFERENCES guest(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (disk_id) REFERENCES disk(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        $queries[] = 
            "CREATE TABLE `snapshot` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `guest_id` int unsigned NOT NULL,
                `disk_id` int unsigned NOT NULL,
                `mem_path` text NOT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (guest_id) REFERENCES guest(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (disk_id) REFERENCES disk(id) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        $queries[] = 
            "CREATE TABLE `disk_dependency` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `parent_disk_id` int unsigned NOT NULL,
                `child_disk_id` int unsigned NOT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (parent_disk_id) REFERENCES disk(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                FOREIGN KEY (child_disk_id) REFERENCES disk(id) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        
        foreach($queries as $query)
        {
            $result = $mysqliConn->query($query);
            
            if ($result === FALSE)
            {
                print "Failed to run query: " . $query . PHP_EOL;
                print $mysqliConn->error . PHP_EOL;
                die();
            }
        }
    }    
    
    
    public function down(\mysqli $mysqliConn) 
    {
        $dropTables = array(
            'disk_dependency',
            'snapshot',
            'guest_disk',
            'guest',
            'disk'
        );
        
        foreach ($dropTables as $table)
        {
            $query = 'DROP table ' . $table;
            $mysqliConn->query($query);
        }
    }
}
