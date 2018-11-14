<?php
/**
 * CodeIgniter-Aauth
 *
 * Aauth is a User Authorization Library for CodeIgniter 4.x, which aims to make
 * easy some essential jobs such as login, permissions and access operations.
 * Despite ease of use, it has also very advanced features like groupping,
 * access management, public access etc..
 *
 * @package   CodeIgniter-Aauth
 * @author    Magefly Team
 * @author    Jacob Tomlinson
 * @author    Tim Swagger (Renowne, LLC) <tim@renowne.com>
 * @author    Raphael Jackstadt <info@rejack.de>
 * @copyright 2014-2017 Emre Akay
 * @copyright 2018 Magefly
 * @license   https://opensource.org/licenses/MIT	MIT License
 * @link      https://github.com/magefly/CodeIgniter-Aauth
 */

namespace App\Models\Aauth;

use CodeIgniter\Model;
use Config\Aauth as AauthConfig;

/**
 * User Model
 *
 * @package CodeIgniter-Aauth
 */
class UserModel extends Model
{
	/**
	 * If this model should use "softDeletes" and
	 * simply set a flag when rows are deleted, or
	 * do hard deletes.
	 *
	 * @var boolean
	 */
	protected $useSoftDeletes = true;

	/**
	 * If true, will set created_at, and updated_at
	 * values during insert and update routines.
	 *
	 * @var boolean
	 */
	protected $useTimestamps = true;

	/**
	 * An array of field names that are allowed
	 * to be set by the user in inserts/updates.
	 *
	 * @var array
	 */
	protected $allowedFields = [
		'email',
		'username',
		'password',
	];

	/**
	 * Callbacks. Each array should contain the method
	 * names (within the model) that should be called
	 * when those events are triggered. With the exception
	 * of 'afterFind', all methods are passed the same
	 * items that are given to the update/insert method.
	 * 'afterFind' will also include the results that were found.
	 *
	 * @var array
	 */
	protected $beforeInsert = ['hashPassword'];

	/**
	 * Callbacks. Each array should contain the method
	 * names (within the model) that should be called
	 * when those events are triggered. With the exception
	 * of 'afterFind', all methods are passed the same
	 * items that are given to the update/insert method.
	 * 'afterFind' will also include the results that were found.
	 *
	 * @var array
	 */
	protected $beforeUpdate = ['hashPassword'];

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->config  = new AauthConfig();
		$this->table   = $this->config->dbTableUsers;
		$this->DBGroup = $this->config->dbProfile;

		$this->validationRules['email']    = 'required|if_exist|valid_email|is_unique[' . $this->table . '.email,id,{id}]';
		$this->validationRules['password'] = 'required|if_exist|min_length[' . $this->config->passwordMin . ']|max_length[' . $this->config->passwordMax . ']';
		$this->validationRules['username'] = 'if_exist|is_unique[' . $this->table . '.username,id,{id}]|alpha_numeric_space|min_length[3]';

		$this->validationMessages = [
			'email'    => [
				'is_unique'   => lang('Aauth.existsAlreadyEmail'),
				'valid_email' => lang('Aauth.invalidEmail'),
			],
			'password' => [
				'min_length' => lang('Aauth.invalidPassword'),
				'max_length' => lang('Aauth.invalidPassword'),
			],
			'username' => [
				'is_unique'  => lang('Aauth.existsAlreadyUsername'),
				'min_length' => lang('Aauth.invalidUsername'),
			],
		];

		if ($this->config->loginUseUsername)
		{
			$this->validationRules['username'] = 'is_unique[' . $this->table . '.username,id,{id}]|required|alpha_numeric_space|min_length[3]';

			$this->validationMessages['username']['required'] = lang('Aauth.requiredUsername');
		}
	}

	/**
	 * Update last login by User ID
	 *
	 * @param integer $userId User id
	 *
	 * @return void
	 */
	public function updateLastLogin(int $userId)
	{
		$builder = $this->builder();

		$data['last_login']    = $this->setDate();
		$data['last_activity'] = $this->setDate();
		$builder->update($data, [$this->primaryKey => $userId]);
	}

	/**
	 * Update Last Activity by User ID
	 *
	 * @param integer $userId User id
	 *
	 * @return void
	 */
	public function updateLastActivity(int $userId)
	{
		$builder = $this->builder();

		$data['last_activity'] = $this->setDate();
		$builder->update($data, [$this->primaryKey => $userId]);
	}

	/**
	 * Checks if user is banned
	 *
	 * @param integer $userId User id
	 *
	 * @return boolean
	 */
	public function isBanned(int $userId)
	{
		$builder = $this->builder();

		if ($this->tempUseSoftDeletes === true)
		{
			$builder->where($this->deletedField, 0);
		}

		$builder->select('banned');
		$builder->where($this->primaryKey, $userId);
		// $builder->where('banned', 1);

		if ($user = $builder->get()->getFirstRow())
		{
			return $user['banned'];
		}

		return false;
	}

	/**
	 * Checks if user exist by user id
	 *
	 * @param integer $userId User id
	 *
	 * @return boolean
	 */
	public function existsById(int $userId)
	{
		$builder = $this->builder();

		if ($this->tempUseSoftDeletes === true)
		{
			$builder->where($this->deletedField, 0);
		}

		$builder->where($this->primaryKey, $userId);
		return ($builder->countAllResults() ? true : false);
	}

	/**
	 * Checks if user exist by email
	 *
	 * @param string $email Email address
	 *
	 * @return boolean
	 */
	public function existsByEmail(string $email)
	{
		$builder = $this->builder();

		if ($this->tempUseSoftDeletes === true)
		{
			$builder->where($this->deletedField, 0);
		}

		$builder->where('email', $email);
		return ($builder->countAllResults() ? true : false);
	}

	/**
	 * Checks if user exist by username
	 *
	 * @param string $username Username
	 *
	 * @return boolean
	 */
	public function existsByUsername(string $username)
	{
		$builder = $this->builder();

		if ($this->tempUseSoftDeletes === true)
		{
			$builder->where($this->deletedField, 0);
		}

		$builder->where('username', $username);
		return ($builder->countAllResults() ? true : false);
	}

	/**
	 * Hash Password Callback
	 *
	 * @param array $data Data array
	 *
	 * @return array
	 */
	protected function hashPassword(array $data)
	{
		if (! isset($data['data']['password']))
		{
			return $data;
		}

		$data['data']['password'] = password_hash($data['data']['password'], $this->config->passwordHashAlgo, $this->config->passwordHashOptions);
		return $data;
	}
}
