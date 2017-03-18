<?php
/*
 * Singleton class for handling all mysql connections. This file is closely related to the 
 * connection_details.json file which contains all the possible database connections
 */

class ConnectionHandler
{
    # array to store all mysql connections.
    private static $s_instance = null;
    
    private $m_connection = null; 
    
    
    private function __construct()
    {
    }
    
    
    public static function get_instance()
    {
        if (self::$s_instance == null)
        {
            self::$s_instance = new ConnectionHandler();
        }
        
        return self::$s_instance;
    }
    
    
    /**
     * Retrieves the appropriate mysql connection
     * @param connectionName - the string name of the connection that we want to get.
     * @return connection - the mysqli connection object.
     */
    public static function get_connection()
    {
        /* @var $instance ConnectionHandler */
        $instance = self::get_instance();
        
        if ($instance->m_connection == null)
        {
            # Non api based connections
                        
            $newConnection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            
            if ($newConnection->connect_errno > 0) 
            {
                throw new \Exception(
                    "Error connecting to " . $connection_name . ": " .
                    $newConnection->connect_error . "<br>" .
                    "Mysqli error number (". $newConnection->connect_errno . ")"
                );
            }
            
            $instance->m_connection = $newConnection;
        }
        
        return $instance->m_connection;
    }
    
    
    /**
     * Escapes a string specifically for the specified connection / database.
     * 
     * @param unescapedString - the string to prepare for database insertion
     * @param connectionName - the name of the connection/database that wish to insert into.
     * 
     * @return escapedString - the unescaped string in its newly escaped form.
     */
    public static function escapeString($unescapedString, $connectionName)
    {
        $connection = self::get_connection($connectionName);
        $escapedString = mysqli_real_escape_string($connection, $unescapedString);
        return $escapedString;
    }
    
    
    /**
     * Closes off a single connection by name.
     * @param connectionName - the name of the connection we wish to close off.
     * @return void - closes connections in this classes member variables.
     */
    public static function close_connection($connectionName)
    {
        $instance = self::get_instance();
        
        if (isset($instance->m_connection[$connectionName]))
        {
            $instance->m_connection[$connectionName]->close();
            unset($instance->m_connection[$connectionName]);
        }
    }
    
    
    /**
     * Handles the closing of all connections that this class has open. This should be called instead 
     * of using mysql_close anywhere.
     * @param void
     * @return void - closes connections in this classes member variables.
     */
    public static function close_connections()
    {
        $instance = self::get_instance();
        
        foreach ($instance->m_connection as $connectionName => $connection)
        {
            $connection->close();
            unset($instance->m_connection[$connectionName]);
        }
    }
}