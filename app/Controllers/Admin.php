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
				if ($this->request->getGet('size') && is_numeric($this->request->getGet('size')))
					$size = $this->request->getGet('size');
				else 
					$size = null;

				if ($this->request->getGet('offset') && is_numeric($this->request->getGet('offset')))
					$offset = $this->request->getGet('offset');
				else
					$offset = 0;

				if ($this->request->getGet('search') && trim($this->request->getGet('search')) != '')
					$search = $this->request->getGet('search');
				else 
					$search = null;

				if ($this->request->getGet('status') && trim($this->request->getGet('status')) != '')
					$status = \strtoupper($this->request->getGet('status'));
				else 
					$status = null;

				$bookings = $this->model->getBookings($size, $offset, $search, $status);
				if ($bookings) {
					$data = [];
					foreach($bookings as $booking) {
						$booking['sender_avatar_url'] = $this->getAbsolutePublicFileUrl(getenv('USERS_AVATAR_DIRECTORY') . $booking['sender_avatar']);
						$data[] = $booking;
					}

					return $this->respond($data);
				}
				else
					return $this->failNotFound('No booking matched your queries.');
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}

	public function getBooking(int $booking_id) {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$admin = $this->authenticateSession();

      if ($admin) {
				// Validation rules
				$validationRules = [
					'booking_id' => 'trim|required|is_not_unique[bookings.id]',
				];

				$data['booking_id'] = $booking_id;

				// Validate the data
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Get the booking
					$booking = $this->model->getBookingData($booking_id);

					return $this->respond($booking);
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

	public function addCoupon() {
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
					'discount'    => 'trim|required|decimal|greater_than[0]',
					'code'        => 'trim|required|alpha_numeric_space|max_length[30]|is_unique[coupons.code]',
					'expiry_date' => 'trim|required|valid_date[Y-m-d H:i:s]',
				];

				// Validate the data
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Create the entries array
					$entries = [
						'discount'      => $data['discount'] / 100,
						'code'          => $data['code'],
						'expiry_date'   => $data['expiry_date'],
					];

					if ($this->model->addCoupon($entries))
						return $this->respondCreated('Coupon added.');
					else
						return $this->fail('Failed to add coupon.');
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
}