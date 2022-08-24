<?php

namespace Ospina\EasyLDAP;

use Dotenv\Dotenv;
use ErrorException;

require 'vendor/autoload.php';

class EasyLDAP

{
    /**
     * @var string
     */
    private string $host;
    /**
     * @var int
     */
    private int $port;
    /**
     * @var string
     */
    private $bindDN = 'cn=admin,dc=unibague,dc=edu,dc=co';
    /**
     * @var string
     */
    private string $baseDN = 'dc=unibague,dc=edu,dc=co';
    /**
     * @var string
     */
    private string $password;
    /**
     * @var false|resource
     */
    private $connection = null;

    public $roles = [
        0 => 'estudiantes',
        1 => 'funcionarios'
    ];

    /**
     *
     * @throws ErrorException
     */
    public function __construct(bool $asAdmin = true, string $envPath = '/../../../../')
    {
        set_error_handler(static function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });


        $dotenv = Dotenv::createUnsafeImmutable(__DIR__ . $envPath);
        $dotenv->load();
        $this->host = getenv('LDAP_HOST');
        $this->password = getenv('LDAP_PASSWORD');
        $this->port = getenv('LDAP_PORT');

        $this->connection = ldap_connect($this->host, $this->port);
        //Configure required options
        $this->configure();

        if ($asAdmin) {
            //Authenticate connection
            $this->authenticateAsAdmin();
        }

    }

    /**
     * @return void
     */
    public function authenticateAsAdmin(): void
    {
        $this->authenticate($this->bindDN, $this->password, 1, true);
    }

    public function authenticate(string $bindDN, string $password, int $role, $custom = false): bool
    {
        if ($custom) {
            return ldap_bind($this->connection, $bindDN, $password);
        }

        $selectedRole = $this->roles[$role];
        $baseDN = $this->baseDN;
        $dn = "uid=${bindDN},ou=${selectedRole},${baseDN}";
        return ldap_bind($this->connection, $dn, $password);
    }

    /**
     * @return void
     */
    private function configure(): void
    {
        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    }

    /**
     * @param array $filters
     * @param string $ou
     * @return array|false
     */
    public function search(array $filters, string $ou = '')
    {
        $baseDn = $this->baseDN;
        $filterString = '(&';
        foreach ($filters as $key => $value) {
            $filterString .= "(${key}=${value})";
        }
        $filterString .= ')';
        if ($ou !== '') {
            $baseDn = "ou=${ou},${baseDn}";
        }
        $search = ldap_search($this->connection, $this->baseDN, $filterString);
        return ldap_get_entries($this->connection, $search);
    }

    /**
     * @param array $filters
     * @param string $ou
     * @return false|array
     */
    public function getFirst(array $filters, string $ou = '')
    {
        $searchResult = $this->search($filters, $ou);
        if (!$searchResult) {
            return false;
        }
        return $searchResult[0];
    }

    /**
     * @param $newDN
     * @return void
     */
    public function changeBaseDN($newDN): void
    {
        $this->baseDN = $newDN;
    }

    /**
     * @param string $dn
     * @param array $attributes
     * @return bool
     */
    public function modify(string $dn, array $attributes): bool
    {
        return ldap_modify($this->connection, $dn, $attributes);
    }

    /**
     * @param string $password
     * @return string
     */
    public static function generateMD5Password(string $password): string
    {
        return "{MD5}" . base64_encode(pack("H*", md5($password)));
    }


}