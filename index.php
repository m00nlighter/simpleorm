<?
class ArrayHelper
{
    public static function toObject( $array ) {
        if ( !is_array($array) ) {
            return $array;
        }

        $object = new stdClass();
        if ( is_array( $array ) && count( $array ) > 0) {
            foreach ( $array as $name => $value ) {
                $name = trim( $name );
                if ( !empty( $name ) )
                {
                    $object->$name = self::toObject( $value );
                }
            }
            return $object;
        }
        else {
            return FALSE;
        }
    }
}
class Config
{
    private static $config;

    public static function get()
    {
        if( !is_object( self::$config ))
        {
            $config = require_once( 'app/config.php' );
            self::$config = ArrayHelper::toObject( $config );
        }
        return self::$config;

    }
}
class Connection
{
    protected static $conn;
    protected static $dbname;

    public function connect( $dbname )
    {
        $settings = Config::get()->$dbname;
        $dsn      = $settings->connectionString;
        $username = $settings->username;
        $password = $settings->password;
        try
        {
            $conn = new PDO( $dsn, $username, $password );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch( PDOException $e )
        {
            echo 'ERROR: ' . $e->getMessage();
        }
        self::$dbname = $dbname;
        self::$conn = $conn;
        return self::$conn;
    }
    public function disconnect()
    {
        self::$conn = NULL;
    }
    public static function current( $dbname )
    {
        if(self::$conn and $dbname == self::$dbname)
            return self::$conn;
        return self::connect( $dbname );
    }
}

class Query
{

    protected $conn;
    protected $select;
    protected $table;
    protected $conditions;
    protected $params;
    protected $offset = 0;
    protected $limit;

    public function __construct( $dbname )
    {
        $this->conn = Connection::current( $dbname );
    }
    public function select( $fields )
    {
        if( is_array( $fields ) )
            $fields = implode(', ', $fields);
        $this->select = $fields;
    }

    public function from( $table )
    {
        $this->table = $table;
    }
    public function where( $conditions, $params )
    {
        $this->conditions = $conditions;
        $this->params = $params;
    }
    public function offset( $from )
    {
        $this->offset = $from;
    }
    public function limit( $to )
    {
        $this->limit = $to;
    }
    public function query()
    {
        if( $this->select )
        {
            $sql = "select $this->select from $this->table";
        }
        if( $this->conditions )
        {
            $sql .=" where $this->conditions";
        }
        if( $this->limit )
        {
            $sql .=" LIMIT $this->offset,$this->limit";
        }
        $query = $this->conn->prepare( $sql );
        if( $this->params )
        {
            foreach($this->params as $param => $value )
            {
                $query->bindParam( $param, $value );
            }
        }
        $query->execute();

        $result = $query->fetchAll();
        return $result;

    }
}
abstract class Model
{

    protected static function build(  $attributes, $conditions, $params = NULL, $limit = NULL )
    {
        $table = get_called_class()::tableName();
        $database = get_called_class()::database();

        $queryBuilder = new Query( $database );
        $queryBuilder->select( $attributes );
        $queryBuilder->from( $table );
        $queryBuilder->where( $conditions, $params );
        $queryBuilder->limit( $limit );
        return $queryBuilder->query();
    }
    public function find( $conditions, $params = NULL )
    {
        return self::build( '*', $conditions, $params, 1 );
    }
    public function findAll( $conditions = NULL, $params = NULL )
    {
        return self::build( '*', $conditions, $params );
    }
    public function findByPk( $pk )
    {
        return self::build( '*', 'id = :id', [':id'=>$pk], 1 );
    }
    public function findByAttributes( $attributes, $conditions, $params = NULL )
    {
        return self::build( $attributes, $conditions, $params, 1  );
    }
    public function findAllByAttributes( $attributes, $conditions, $params = NULL ){
        return self::build( $attributes, $conditions, $params );
    }
}
class User extends Model
{
    public static function database()
    {
        return 'db1';
    }
    public static function tableName()
    {
        return 'its_users';
    }
}
class Contact extends Model
{
    public static function database()
    {
        return 'db1';
    }
    public static function tableName()
    {
        return 'its_user_contacts';
    }
}


$res = User::findAll('name like :name',[':name'=>'_mytro']);
var_dump($res);
