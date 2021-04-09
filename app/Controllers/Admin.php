<?php namespace App\Controllers;

class Admin extends BaseController
{
	protected $session_name = 'admin_session_data';
	
	public function __construct() {
		$this->model = \model('App\Models\AdminModel', true);
	}
  
  private function authenticate() {
		if (! $this->request->hasHeader('Authorization'))
			return FALSE;

		$credentials = $this->request->getHeader('Authorization')->getValue();
		$credentials = \explode(' ', $credentials);

		if (\count($credentials) < 2)
			return FALSE;

		if ($credentials[0] !== 'Basic')
			return FALSE;
		
		$credentials = $credentials[1];
		$credentials = \base64_decode($credentials, TRUE);

		if (! $credentials)
			return FALSE;

		$credentials = \explode(':', $credentials);

		if (\count($credentials) < 2)
			return FALSE;

		$username = $credentials[0];
		$password = $credentials[1];

		$data = $this->model->getAdminData($username);

		if (! $data)
			return FALSE;

		if (password_verify($password, $data['password'])) {
			return $data;
		}
		else
			return FALSE;
	}

	private function authenticateSession() {
    // Check if a valid session exists
    if ($this->session->has($this->session_name)) {
			$data = $this->session->get($this->session_name);
			return $data;
		}
    else {
      // No session data found. Let's try other authentication method(s)
      // We can authenticate if a BASIC AUTH header was provided.
      return $this->authenticate();
		}
	}

	public function createSession() {
		try {
      // Set the headers
      $this->setDefaultHeaders();

      $admin = $this->authenticateSession();

      if ($admin) {
        //unset($admin['id']);
				unset($admin['password']);
				
				$this->session->set($this->session_name, $admin);
        return $this->respond($admin);
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}
	
	public function clearSession() {
		try {
			// Set the headers
			$this->setDefaultHeaders();

			// Clear the current session
			$this->session->destroy();
			return $this->respondDeleted('Session cleared');
		} catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
		}
	}

	public function getBookings() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$admin = $this->authenticateSession();

      if ($admin) {
				$bookings = $this->model->getBookings();
				if ($bookings) {
					return $this->respond($bookings);
				}
				else
					return $this->failNotFound('No booking yet.');
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}

	public function addShow() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$admin = $this->authenticateSession();

      if ($admin) {
				// Retrieve the sent data
				$data = $this->request->getJSON(true);
				if (!$data)
					return $this->failNotFound('No valid data was provided!');

				// Validation rules
				$validationRules = [
					'name'        => 'trim|required|alpha_numeric_space|max_length[300]|is_unique[shows.name]',
					'description' => 'trim|required|alpha_numeric_punct|max_length[1000]',
					'image'       => 'trim|required',
					'start_date'  => 'trim|required|valid_date[Y-m-d H:i:s]',
					'end_date'    => 'trim|required|valid_date[Y-m-d H:i:s]',
				];

				// Validate the data
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Cend_datereate the entries array
					$entries = [
						'name'        => $data['name'],
						'description' => $data['description'],
						'image'       => $data['image'],
						'start_date'       => $data['start_date'],
						'end_date'       => $data['end_date'],
					];

					if ($this->model->addShow($entries))
						return $this->respondCreated('Show added.');
					else
						return $this->fail('Failed to add show.');
				}
				else {
					return $this->failValidationError($this->arrayToString($this->validation->getErrors()));
				}
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}

	public function getShows() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$admin = $this->authenticateSession();

      if ($admin) {
				$shows = $this->model->getShows();
				if ($shows) {
					return $this->respond($shows);
				}
				else
					return $this->failNotFound('No shows yet.');
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}

	public function getTickets() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$admin = $this->authenticateSession();

      if ($admin) {
				$tickets = $this->model->getTickets();
				if ($tickets) {
					return $this->respond($tickets);
				}
				else
					return $this->failNotFound('No tickets yet.');
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}
}