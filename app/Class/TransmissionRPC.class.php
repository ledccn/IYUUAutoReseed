<?php
/**
 * Transmission bittorrent client/daemon RPC communication class
 * Copyright (C) 2010 Johan Adriaans <johan.adriaans@gmail.com>,
 *                    Bryce Chidester <bryce@cobryce.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * PHP version specific information
 * version_compare() (PHP 4 >= 4.1.0, PHP 5)
 * ctype_digit() (PHP 4 >= 4.0.4, PHP 5)
 * stream_context_create (PHP 4 >= 4.3.0, PHP 5)
 * PHP Class support (PHP 5) (PHP 4 might work, untested)
 */

/**
 * A friendly little version check...
 */
if ( version_compare( PHP_VERSION, TransmissionRPC::MIN_PHPVER, '<' ) )
  die( "The TransmissionRPC class requires PHP version {TransmissionRPC::TRANSMISSIONRPC_MIN_PHPVER} or above." . PHP_EOL );

/**
 * Transmission bittorrent client/daemon RPC communication class
 *
 * Usage example:
 * <code>
 *   $rpc = new TransmissionRPC($rpc_url);
 *   $result = $rpc->add_file( $url_or_path_to_torrent, $target_folder );
 * </code>
 *
 */
class TransmissionRPC
{
  /**
   * User agent used in all http communication
   */
  const HTTP_UA = 'TransmissionRPC for PHP/0.3';

  /**
   * Minimum PHP version required
   * 5.2.10 implemented the required http stream ignore_errors option
   */
  const MIN_PHPVER = '5.2.10';

  /**
   * The URL to the bittorent client you want to communicate with
   * the port (default: 9091) can be set in you Transmission preferences
   * @var string
   */
  public $url = '';

  /**
   * If your Transmission RPC requires authentication, supply username here 
   * @var string
   */
  public $username = '';

  /**
   * If your Transmission RPC requires authentication, supply password here 
   * @var string
   */
  public $password = '';

  /**
   * Return results as an array, or an object (default)
   * @var bool
   */
  public $return_as_array = false;

  /**
   * Print debugging information, default is off
   * @var bool
   */
  public $debug = false;

  /**
   * Transmission RPC version
   * @var int
   */
  protected $rpc_version = 0;

  /**
   * Transmission uses a session id to prevent CSRF attacks
   * @var string 
   */
  protected $session_id = '';

  /**
   * Default values for stream context
   * @var array
   */
  private $default_context_opts = array( 'http' => array(
                                           'user_agent'  => self::HTTP_UA,
                                           'timeout' => '60',	// Don't want to be too slow
                                           'ignore_errors' => true,	// Leave the error parsing/handling to the code
                                         )
                                       );

  /**
   * Constants for torrent status
   */
  const TR_STATUS_STOPPED       = 0;
  const TR_STATUS_CHECK_WAIT    = 1;
  const TR_STATUS_CHECK         = 2;
  const TR_STATUS_DOWNLOAD_WAIT = 3;
  const TR_STATUS_DOWNLOAD      = 4;
  const TR_STATUS_SEED_WAIT     = 5;
  const TR_STATUS_SEED          = 6;

  const RPC_LT_14_TR_STATUS_CHECK_WAIT = 1;
  const RPC_LT_14_TR_STATUS_CHECK      = 2;
  const RPC_LT_14_TR_STATUS_DOWNLOAD   = 4;
  const RPC_LT_14_TR_STATUS_SEED       = 8;
  const RPC_LT_14_TR_STATUS_STOPPED    = 16;

  /**
   * Start one or more torrents
   *
   * @param int|array ids A list of transmission torrent ids
   */
  public function start ( $ids )
  {
    if ( !is_array( $ids ) ) $ids = array( $ids );	// Convert $ids to an array if only a single id was passed
    $request = array( "ids" => $ids );
    return $this->request( "torrent-start", $request );
  }

  /**
   * Stop one or more torrents
   *
   * @param int|array ids A list of transmission torrent ids
   */
  public function stop ( $ids )
  {
    if ( !is_array( $ids ) ) $ids = array( $ids );	// Convert $ids to an array if only a single id was passed
    $request = array( "ids" => $ids );
    return $this->request( "torrent-stop", $request );
  }

  /**
   * Reannounce one or more torrents
   *
   * @param int|array ids A list of transmission torrent ids
   */
  public function reannounce ( $ids )
  {
    if ( !is_array( $ids ) ) $ids = array( $ids );	// Convert $ids to an array if only a single id was passed
    $request = array( "ids" => $ids );
    return $this->request( "torrent-reannounce", $request );
  }

  /**
   * Verify one or more torrents
   *
   * @param int|array ids A list of transmission torrent ids
   */
  public function verify ( $ids )
  {
    if ( !is_array( $ids ) ) $ids = array( $ids );	// Convert $ids to an array if only a single id was passed
    $request = array( "ids" => $ids );
    return $this->request( "torrent-verify", $request );
  }

  /**
   * Get information on torrents in transmission, if the ids parameter is 
   * empty all torrents will be returned. The fields array can be used to return certain
   * fields. Default fields are: "id", "name", "status", "doneDate", "haveValid", "totalSize".
   * See https://github.com/transmission/transmission/blob/2.9x/extras/rpc-spec.txt for available fields
   *
   * @param array fields An array of return fields
   * @param int|array ids A list of transmission torrent ids
   *    
    Request: 
      {
         "arguments": {
             "fields": [ "id", "name", "totalSize" ],
             "ids": [ 7, 10 ]
         },
         "method": "torrent-get",
         "tag": 39693
      }

    Response:
      {
         "arguments": {
            "torrents": [ 
               { 
                   "id": 10,
                   "name": "Fedora x86_64 DVD",
                   "totalSize": 34983493932,
               },
               {
                   "id": 7,
                   "name": "Ubuntu x86_64 DVD",
                   "totalSize", 9923890123,
               } 
            ]
         },
         "result": "success",
         "tag": 39693
      }
   */
  public function get ( $ids = array(), $fields = array() )
  {
    if ( !is_array( $ids ) ) $ids = array( $ids );	// Convert $ids to an array if only a single id was passed
    if ( count( $fields ) == 0 ) $fields = array( "id", "name", "status", "doneDate", "haveValid", "totalSize" );	// Defaults
    $request = array(
      "fields" => $fields,
      "ids" => $ids
    );
    return $this->request( "torrent-get", $request );
  }

  /**
   * Set properties on one or more torrents, available fields are:
   *   "bandwidthPriority"   | number     this torrent's bandwidth tr_priority_t
   *   "downloadLimit"       | number     maximum download speed (in K/s)
   *   "downloadLimited"     | boolean    true if "downloadLimit" is honored
   *   "files-wanted"        | array      indices of file(s) to download
   *   "files-unwanted"      | array      indices of file(s) to not download
   *   "honorsSessionLimits" | boolean    true if session upload limits are honored
   *   "ids"                 | array      torrent list, as described in 3.1
   *   "location"            | string     new location of the torrent's content
   *   "peer-limit"          | number     maximum number of peers
   *   "priority-high"       | array      indices of high-priority file(s)
   *   "priority-low"        | array      indices of low-priority file(s)
   *   "priority-normal"     | array      indices of normal-priority file(s)
   *   "seedRatioLimit"      | double     session seeding ratio
   *   "seedRatioMode"       | number     which ratio to use.  See tr_ratiolimit
   *   "uploadLimit"         | number     maximum upload speed (in K/s)
   *   "uploadLimited"       | boolean    true if "uploadLimit" is honored
   * See https://github.com/transmission/transmission/blob/2.9x/extras/rpc-spec.txt for more information
   *
   * @param array arguments An associative array of arguments to set
   * @param int|array ids A list of transmission torrent ids
   */  
  public function set ( $ids = array(), $arguments = array() )
  {
    // See https://github.com/transmission/transmission/blob/2.9x/extras/rpc-spec.txt for available fields
    if ( !is_array( $ids ) ) $ids = array( $ids );	// Convert $ids to an array if only a single id was passed
    if ( !isset( $arguments['ids'] ) ) $arguments['ids'] = $ids;	// Any $ids given in $arguments overrides the method parameter
    return $this->request( "torrent-set", $arguments );
  }

  /**
   * Add a new torrent
   *
   * Available extra options:
   *  key                  | value type & description
   *  ---------------------+-------------------------------------------------
   *  "download-dir"       | string      path to download the torrent to
   *  "filename"           | string      filename or URL of the .torrent file
   *  "metainfo"           | string      base64-encoded .torrent content
   *  "paused"             | boolean     if true, don't start the torrent
   *  "peer-limit"         | number      maximum number of peers
   *  "bandwidthPriority"  | number      torrent's bandwidth tr_priority_t
   *  "files-wanted"       | array       indices of file(s) to download
   *  "files-unwanted"     | array       indices of file(s) to not download
   *  "priority-high"      | array       indices of high-priority file(s)
   *  "priority-low"       | array       indices of low-priority file(s)
   *  "priority-normal"    | array       indices of normal-priority file(s)
   *  
   *   Either "filename" OR "metainfo" MUST be included.
   *     All other arguments are optional.   
   *
   * @param torrent_location The URL or path to the torrent file
   * @param save_path Folder to save torrent in
   * @param extra options Optional extra torrent options
   */
  public function add_file ( $torrent_location, $save_path = '', $extra_options = array() )
  {
    if(!empty($save_path)) $extra_options['download-dir'] = $save_path;
    $extra_options['filename'] = $torrent_location;
    
    return $this->request( "torrent-add", $extra_options );
  }

  /**
   * Add a torrent using the raw torrent data
   *
   * @param torrent_metainfo The raw, unencoded contents (metainfo) of a torrent
   * @param save_path Folder to save torrent in
   * @param extra options Optional extra torrent options
   */
  public function add_metainfo ( $torrent_metainfo, $save_path = '', $extra_options = array() )
  {
    $extra_options['download-dir'] = $save_path;
    $extra_options['metainfo'] = base64_encode( $torrent_metainfo );
    
    return $this->request( "torrent-add", $extra_options );
  }

  /* Add a new torrent using a file path or a URL (For backwards compatibility)
   * @param torrent_location The URL or path to the torrent file
   * @param save_path Folder to save torrent in
   * @param extra options Optional extra torrent options
   */
  public function add ( $torrent_location, $save_path = '', $extra_options = array() )
  {
    return $this->add_file( $torrent_location, $save_path, $extra_options );
  }

  /**
   * Remove torrent from transmission
   *
   * @param bool delete_local_data Also remove local data?
   * @param int|array ids A list of transmission torrent ids
   */
  public function remove ( $ids, $delete_local_data = false )
  {
    if ( !is_array( $ids ) ) $ids = array( $ids );	// Convert $ids to an array if only a single id was passed
    $request = array(
      "ids" => $ids,
      "delete-local-data" => $delete_local_data
    );
    return $this->request( "torrent-remove", $request );
  }

  /**
   * Move local storage location
   *
   * @param int|array ids A list of transmission torrent ids
   * @param string target_location The new storage location
   * @param string move_existing_data Move existing data or scan new location for available data
   */
  public function move ( $ids, $target_location, $move_existing_data = true )
  {
    if ( !is_array( $ids ) ) $ids = array( $ids );	// Convert $ids to an array if only a single id was passed
    $request = array(
      "ids" => $ids,
      "location" => $target_location,
      "move" => $move_existing_data
    );
    return $this->request( "torrent-set-location", $request );  
  }
  
  /**
   * 3.7.  Renaming a Torrent's Path
   * 
   * Method name: "torrent-rename-path"
   * 
   * For more information on the use of this function, see the transmission.h
   * documentation of tr_torrentRenamePath(). In particular, note that if this
   * call succeeds you'll want to update the torrent's "files" and "name" field
   * with torrent-get.
   *
   * Request arguments:
   * 
   * string                           | value type & description
   * ---------------------------------+-------------------------------------------------
   * "ids"                            | array      the torrent torrent list, as described in 3.1
   *                                  |            (must only be 1 torrent)
   * "path"                           | string     the path to the file or folder that will be renamed
   * "name"                           | string     the file or folder's new name
 
   * Response arguments: "path", "name", and "id", holding the torrent ID integer
   *
   * @param int|array ids A 1-element list of transmission torrent ids
   * @param string path The path to the file or folder that will be renamed
   * @param string name The file or folder's new name
   */
  public function rename ( $ids, $path, $name )
  {
    if ( !is_array( $ids ) ) $ids = array( $ids );  // Convert $id to an array if only a single id was passed
    if ( count( $ids ) !== 1 ) {
      throw new TransmissionRPCException( 'A single id is accepted', TransmissionRPCException::E_INVALIDARG );
    }

    $request = array(
      "ids" => $ids,
      "path" => $path,
      "name" => $name
    );
    return $this->request( "torrent-rename-path", $request );  
  }


  /**
   * Retrieve session statistics
   *
   * @returns array of statistics
   */
  public function sstats ( )
  {
    return $this->request( "session-stats", array() );
  }

  /**
   * Retrieve all session variables
   *
   * @returns array of session information
   */
  public function sget ( )
  {
    return $this->request( "session-get", array() );
  }

  /**
   * Set session variable(s)
   *
   * @param array of session variables to set
   */
  public function sset ( $arguments )
  {
    return $this->request( "session-set", $arguments );
  }

  /**
   * Return the interpretation of the torrent status
   *
   * @param int The integer "torrent status"
   * @returns string The translated meaning
   */  
  public function getStatusString ( $intstatus )
  {
    if($this->rpc_version < 14){
      if( $intstatus == self::RPC_LT_14_TR_STATUS_CHECK_WAIT )
        return "Waiting to verify local files";
      if( $intstatus == self::RPC_LT_14_TR_STATUS_CHECK )
        return "Verifying local files";
      if( $intstatus == self::RPC_LT_14_TR_STATUS_DOWNLOAD )
        return "Downloading";
      if( $intstatus == self::RPC_LT_14_TR_STATUS_SEED )
        return "Seeding";
      if( $intstatus == self::RPC_LT_14_TR_STATUS_STOPPED )
        return "Stopped";
    }else{
      if( $intstatus == self::TR_STATUS_CHECK_WAIT )
        return "Waiting to verify local files";
      if( $intstatus == self::TR_STATUS_CHECK )
        return "Verifying local files";
      if( $intstatus == self::TR_STATUS_DOWNLOAD )
        return "Downloading";
      if( $intstatus == self::TR_STATUS_SEED )
        return "Seeding";
      if( $intstatus == self::TR_STATUS_STOPPED )
        return "Stopped";
      if( $intstatus == self::TR_STATUS_SEED_WAIT )
        return "Queued for seeding";
      if( $intstatus == self::TR_STATUS_DOWNLOAD_WAIT )
        return "Queued for download";
    }
    return "Unknown";
  }



  /**
   * Here be dragons (Internal methods)
   */



  /**
   * Clean up the request array. Removes any empty fields from the request
   *
   * @param array array The request associative array to clean
   * @returns array The cleaned array
   */  
  protected function cleanRequestData ( $array )
  {
    if ( !is_array( $array ) || count( $array ) == 0 ) return null;	// Nothing to clean
    setlocale( LC_NUMERIC, 'en_US.utf8' );	// Override the locale - if the system locale is wrong, then 12.34 will encode as 12,34 which is invalid JSON
    foreach ( $array as $index => $value )
    {
      if( is_object( $value ) ) $array[$index] = $value->toArray();	// Convert objects to arrays so they can be JSON encoded
      if( is_array( $value ) ) $array[$index] = $this->cleanRequestData( $value );	// Recursion
      if( empty( $value ) && $value !== 0 )	// Remove empty members
      {
        unset( $array[$index] );
        continue; // Skip the rest of the tests - they may re-add the element.
      }
      if( is_numeric( $value ) ) $array[$index] = $value+0;	// Force type-casting for proper JSON encoding (+0 is a cheap way to maintain int/float/etc)
      if( is_bool( $value ) ) $array[$index] = ( $value ? 1 : 0);	// Store boolean values as 0 or 1
      if( is_string( $value ) ) {
        if ( mb_detect_encoding($value,"auto") !== 'UTF-8' ) {
          $array[$index] = mb_convert_encoding($value, "UTF-8");
          //utf8_encode( $value );	// Make sure all data is UTF-8 encoded for Transmission
        }      
      }
    }
    return $array;
  }

  /**
   * Clean up the result object. Replaces all minus(-) characters in the object properties with underscores
   * and converts any object with any all-digit property names to an array.
   *
   * @param object The request result to clean
   * @returns array The cleaned object
   */  
  protected function cleanResultObject ( $object )
  {
    // Prepare and cast object to array
    $return_as_array = false;
    $array = $object;
    if ( !is_array( $array ) ) $array = (array) $array;
    foreach ( $array as $index => $value )
    {
      if( is_array( $array[$index] ) || is_object( $array[$index] ) )
      {
        $array[$index] = $this->cleanResultObject( $array[$index] );	// Recursion
      }
      if ( strstr( $index, '-' ) )
      {
        $valid_index = str_replace( '-', '_', $index );
        $array[$valid_index] = $array[$index]; 
        unset( $array[$index] );
        $index = $valid_index;
      }
      // Might be an array, check index for digits, if so, an array should be returned
      if ( ctype_digit( (string) $index ) ) { $return_as_array = true; }
      if ( empty( $value ) ) unset( $array[$index] );
    }
    // Return array cast to object
    return $return_as_array ? $array : (object) $array;
  }
  
  /**
   * 执行 rpc 请求
   *
   * @param $method 请求类型/方法, 详见 $this->allowMethods
   * @param array $arguments 附加参数, 可选
   * @return mixed
   */
  protected function request($method, $arguments = array())
  {
    // Check the parameters
    if ( !is_scalar( $method ) )
      throw new TransmissionRPCException( 'Method name has no scalar value', TransmissionRPCException::E_INVALIDARG );
    if ( !is_array( $arguments ) )
      throw new TransmissionRPCException( 'Arguments must be given as array', TransmissionRPCException::E_INVALIDARG );
    
    $arguments = $this->cleanRequestData( $arguments );	// Sanitize input
    
    // Grab the X-Transmission-Session-Id if we don't have it already
    if( !$this->session_id )
      if( !$this->GetSessionID() )
        throw new TransmissionRPCException( 'Unable to acquire X-Transmission-Session-Id', TransmissionRPCException::E_SESSIONID );

    $data = array(
        'method' => $method,
        'arguments' => $arguments
    );

    $header = array(
        'Content-Type: application/json',
        'Authorization: Basic '.base64_encode(sprintf("%s:%s", $this->username, $this->password)),
        'X-Transmission-Session-Id: '.$this->session_id
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $this->username.':'.$this->password);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    $content = curl_exec($ch);
    curl_close($ch);

    if (!$content)  $content = json_encode(array('result' => 'failed'));
    return $this->return_as_array ? json_decode( $content, true ) : $this->cleanResultObject( json_decode( $content ) );	// Return the sanitized result
  }
  /**
   * Performs an empty GET on the Transmission RPC to get the X-Transmission-Session-Id
   * and store it in $this->session_id
   *
   * @return string
   */
  public function GetSessionID()
  {
    if( !$this->url )
      throw new TransmissionRPCException( "Class must be initialized before GetSessionID() can be called.", TransmissionRPCException::E_INVALIDARG );
    
    // Setup the context
    $contextopts = $this->default_context_opts;	// Start with the defaults
    
    // Make sure it's blank/empty (reset)
    $this->session_id = null;
    
    // Setup authentication (if provided)
    if ( $this->username && $this->password )
      $contextopts['http']['header'] = sprintf( "Authorization: Basic %s\r\n", base64_encode( $this->username.':'.$this->password ) );
    
    if( $this->debug ) echo "TRANSMISSIONRPC_DEBUG:: GetSessionID():: Stream context created with options:".
                            PHP_EOL . print_r( $contextopts, true );
    
    $context  = stream_context_create( $contextopts );	// Create the context for this request
    if ( ! $fp = @fopen( $this->url, 'r', false, $context ) )	// Open a filepointer to the data, and use fgets to get the result
      throw new TransmissionRPCException( 'Unable to connect to '.$this->url, TransmissionRPCException::E_CONNECTION );
    
    // Check the response (headers etc)
    $stream_meta = stream_get_meta_data( $fp );
    fclose( $fp );
    if( $this->debug ) echo "TRANSMISSIONRPC_DEBUG:: GetSessionID():: Stream meta info: ".
                            PHP_EOL . print_r( $stream_meta, true );
    if( $stream_meta['timed_out'] )
      throw new TransmissionRPCException( "Timed out connecting to {$this->url}", TransmissionRPCException::E_CONNECTION );
    if( substr( $stream_meta['wrapper_data'][0], 9, 3 ) == "401" )
      throw new TransmissionRPCException( "Invalid username/password.", TransmissionRPCException::E_AUTHENTICATION );
    elseif( substr( $stream_meta['wrapper_data'][0], 9, 3 ) == "409" )	// This is what we're hoping to find
    {
      // Loop through the returned headers and extract the X-Transmission-Session-Id
      foreach( $stream_meta['wrapper_data'] as $header )
      {
        if( strpos( $header, 'X-Transmission-Session-Id: ' ) === 0 )
        {
          if( $this->debug ) echo "TRANSMISSIONRPC_DEBUG:: GetSessionID():: Session-Id header: ".
                                  PHP_EOL . print_r( $header, true );
          $this->session_id = trim( substr( $header, 27 ) );
          break;
        }
      }
      if( ! $this->session_id ) {	// Didn't find a session_id
        throw new TransmissionRPCException( "Unable to retrieve X-Transmission-Session-Id", TransmissionRPCException::E_SESSIONID );
      }
    } else {
      throw new TransmissionRPCException( "Unexpected response from Transmission RPC: ".$stream_meta['wrapper_data'][0] );
    }
    return $this->session_id;
  }

  /**
   * Takes the connection parameters
   *
   * TODO: Sanitize username, password, and URL
   *
   * @param string $url
   * @param string $username
   * @param string $password
   */
  public function __construct( $url = 'http://localhost:9091/transmission/rpc', $username = null, $password = null, $return_as_array = false )
  {
    // server URL
    $this->url = $url;
    
    // Username & password
    $this->username = $username;
    $this->password = $password;
    
    // Get the Transmission RPC_version
    $this->rpc_version = self::sget()->arguments->rpc_version;
    
    // Return As Array
    $this->return_as_array = $return_as_array;
    
    // Reset X-Transmission-Session-Id so we (re)fetch one
    $this->session_id = null;
  }
}

/**
 * This is the type of exception the TransmissionRPC class will throw
 */
class TransmissionRPCException extends Exception
{
  /**
   * Exception: Invalid arguments
   */
  const E_INVALIDARG = -1;

  /**
   * Exception: Invalid Session-Id
   */
  const E_SESSIONID = -2;

  /**
   * Exception: Error while connecting
   */
  const E_CONNECTION = -3;

  /**
   * Exception: Error 401 returned, unauthorized
   */
  const E_AUTHENTICATION = -4;

  /**
   * Exception constructor
   */
  public function __construct( $message = null, $code = 0, Exception $previous = null )
  {
    // PHP version 5.3.0 and above support Exception linking
    if ( version_compare( PHP_VERSION, '5.3.0', '>=' ) )
      parent::__construct( $message, $code, $previous );
    else
      parent::__construct( $message, $code );
  }
}

?>
