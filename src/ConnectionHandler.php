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
    
    
    public static function getInstance()
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
    public static function getConnection()
    {
        /* @var $instance ConnectionHandler */
        $instance = self::getInstance();
        
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
     * Closes off a single connection by name.
     * @param connectionName - the name of the connection we wish to close off.
     * @return void - closes connections in this classes member variables.
     */
    public static function close($connectionName)
    {
        $instance = self::getInstance();
        
        if (isset($instance->m_connection[$connectionName]))
        {
            $instance->m_connection[$connectionName]->close();
            unset($instance->m_connection[$connectionName]);
        }
    }
}