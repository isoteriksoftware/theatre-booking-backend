<?php namespace App\Controllers;

class Admin extends BaseController
{
	protected $session_name = 'admin_session_data';
	
	public function __construct() {
		$this->model = \model('App\Models\AdminModel', true);
	}
  
	private function generateBookingID(int $user_id) {
    helper('text'); 
    return '#' . \strtoupper(\random_string('alnum', 4)) . $user_id;
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

		$email = $credentials[0];
		$password = $credentials[1];

		$data = $this->model->getAdminData($email);

		if (! $data)
			return FALSE;

		if (password_verify($password, $data['password'])) {
			// Set the avatar's url
			$data['avatar_url'] = $this->getAbsolutePublicFileUrl(getenv('ADMINS_AVATAR_DIRECTORY') . $data['avatar']);
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

	public function getHoldovers() {
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

				$bookings = $this->model->getHoldovers($size, $offset);
				if ($bookings)
					return $this->respond($bookings);
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

	public function getUserBookings($user_id) {
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

				$bookings = $this->model->getUserBookings($user_id, $size, $offset);
				if ($bookings)
					return $this->respond($bookings);
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

	public function getOverview() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$admin = $this->authenticateSession();

      if ($admin) {
				$overview['riders'] = $this->model->getTotalRiders()['total'];
				$overview['users'] = $this->model->getTotalUsers()['total'];
				$overview['companies'] = $this->model->getTotalCompanies()['total'];
				$overview['deliveries'] = $this->model->getTotalDeliveries()['total'];
				$overview['holdovers'] = $this->model->getTotalHoldovers()['total'];
				$overview['bookings'] = $this->model->getTotalBookings()['total'];

				$pending_bookings = 0;
				$assigned_bookings = 0;
				$in_progress_bookings = 0;
				
				$data = $this->model->getBookingsConstrainedTo(['status' => 'PENDING']);
				if ($data)
					$pending_bookings = \count($data);

				$data = $this->model->getBookingsConstrainedTo(['status' => 'ASSIGNED']);
				if ($data)
					$assigned_bookings = \count($data);

				$data = $this->model->getBookingsConstrainedTo(['status' => 'IN_PROGRESS']);
				if ($data)
					$in_progress_bookings = \count($data);

				$overview['pending_bookings'] = $pending_bookings;
				$overview['assigned_bookings'] = $assigned_bookings;
				$overview['in_progress_bookings'] = $in_progress_bookings;

				$available_riders = $this->model->getPrioritarizedRidersByPendingDeliveries();
				if ($available_riders) {
					$data = [];
					foreach($available_riders as $rider){
						$rider['avatar_url'] = $this->getAbsolutePublicFileUrl(getenv('RIDERS_AVATAR_DIRECTORY') . $rider['avatar']);
						unset($rider['password']);

						$data[] = $rider;
					}

					$overview['available_riders'] = $data;
				}
				else
					$overview['available_riders'] = [];

				$overview['top_customers'] = $this->model->getTopUsers();
				return $this->respond($overview);
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}

	public function getRiders() {
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

				$riders = $this->model->getRiders($size, $offset, $search);
				if ($riders) {
					$data = [];
					foreach($riders as $rider) {
						$rider['avatar_url'] = $this->getAbsolutePublicFileUrl(getenv('RIDERS_AVATAR_DIRECTORY') . $rider['avatar']);
						$rider['deliveries'] = $this->model->getTotalRiderDeliveries($rider['id'])['total'];
						$rider['bookings']   = $this->model->getRiderBookings($rider['id'], $size, $offset);

						$bookings = [];
						foreach($rider['bookings'] as $booking) {
							$booking['sender_avatar_url'] = $this->getAbsolutePublicFileUrl(getenv('USERS_AVATAR_DIRECTORY') . $booking['sender_avatar']);
							$bookings[] = $booking;
						}

						$rider['bookings'] = $bookings;
						$data[] = $rider;
					}

					return $this->respond($data);
				}
				else
					return $this->failNotFound('No data matched your queries.');
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}

	public function getRider(int $rider_id) {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$admin = $this->authenticateSession();

      if ($admin) {
				// Validation rules
				$validationRules = [
					'rider_id' => 'trim|required|is_not_unique[riders.id]',
				];

				$data['rider_id'] = $rider_id;

				// Validate the data
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Get the rider
					$rider = $this->model->getRiderData($rider_id);

					return $this->respond($rider);
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

	public function getUsers() {
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

				$users = $this->model->getUsers($size, $offset, $search);
				if ($users) {
					$data = [];
					foreach($users as $user) {
						$user['avatar_url'] = $this->getAbsolutePublicFileUrl(getenv('USERS_AVATAR_DIRECTORY') . $user['avatar']);
						$user['bookings']   = $this->model->getRiderBookings($user['id'], $size, $offset);

						$bookings = [];
						foreach($user['bookings'] as $booking) {
							$booking['sender_avatar_url'] = $this->getAbsolutePublicFileUrl(getenv('USERS_AVATAR_DIRECTORY') . $booking['sender_avatar']);
							$bookings[] = $booking;
						}

						$user['bookings'] = $bookings;
					
						$data[] = $user;
					}

					return $this->respond($data);
				}
				else
					return $this->failNotFound('No data matched your queries.');
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}

	public function getUser(int $user_id) {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$admin = $this->authenticateSession();

      if ($admin) {
				// Validation rules
				$validationRules = [
					'user_id' => 'trim|required|is_not_unique[users.id]',
				];

				$data['user_id'] = $user_id;

				// Validate the data
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Get the user
					$user = $this->model->getUserData($user_id);

					return $this->respond($user);
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

	public function getCoupons() {
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

				$coupons = $this->model->getCoupons($size, $offset);
				if ($coupons)
					return $this->respond($coupons);
				else
					return $this->failNotFound('No coupon matched your queries.');
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}

	public function getCoupon(int $coupon_id) {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$admin = $this->authenticateSession();

      if ($admin) {
				// Validation rules
				$validationRules = [
					'coupon_id' => 'trim|required|is_not_unique[coupons.id]',
				];

				$data['coupon_id'] = $coupon_id;

				// Validate the data
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Get the coupon
					$coupon = $this->model->getCouponData($coupon_id);

					return $this->respond($coupon);
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

	public function deleteCoupon(int $coupon_id) {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			$admin = $this->authenticateSession();

      if ($admin) {
				// Validation rules
				$validationRules = [
					'coupon_id' => 'trim|required|is_not_unique[coupons.id]',
				];

				$data['coupon_id'] = $coupon_id;

				// Validate the data
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					if ($this->model->deleteCoupon($coupon_id))
						return $this->respondDeleted('Coupon deleted.');
					else 
						return $this->fail('Failed to delete coupon.');
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

	public function getFeedbacks() {
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

				$feedbacks = $this->model->getFeedbacks($size, $offset);
				if ($feedbacks) {
					$data = [];
					foreach($feedbacks as $feedback) {
						$feedback['avatar_url'] = $this->getAbsolutePublicFileUrl(getenv('USERS_AVATAR_DIRECTORY') . $feedback['avatar']);
						$data[] = $feedback;
					}

					return $this->respond($data);
				}
				else
					return $this->failNotFound('No feedback matched your queries.');
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}

	public function getRefunds() {
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

				$refunds = $this->model->getRefunds($size, $offset);
				if ($refunds) {
					return $this->respond($refunds);
				}
				else
					return $this->failNotFound('No data matched your queries.');
      }
      else {
				return $this->failUnauthorized('Authentication failed!');
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}

	public function addRefund() {
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
					'user_full_name' => 'trim|required|alpha_numeric_space|max_length[50]',
					'amount'         => 'trim|required|numeric|greater_than[0]',
					'reason'         => 'trim|required|alpha_numeric_punct|max_length[1000]',
					'status'         => 'trim|required|in_list[PENDING, CANCELLED, IN PROGRESS, COMPLETED]',
				];

				// Validate the data
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Create the entries array
					$entries = [
						'user_full_name' => $data['user_full_name'],
						'amount'         => $data['amount'],
						'reason'         => $data['reason'],
						'status'         => $data['status'],
					];

					if ($this->model->addRefund($entries))
						return $this->respondCreated('Refund added.');
					else
						return $this->fail('Failed to add refund.');
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

	public function updateRefund($refund_id) {
		try {
      // Set the headers
      $this->setDefaultHeaders();

      $admin = $this->authenticateSession();

      if ($admin) {
				// Retrieve the sent data
				$data = $this->request->getJSON(true);

				if (! $data) {
					return $this->failNotFound('No valid data was provided!');
				}

				$validationRules = [];
				$entries         = [];

				if (isset($data['user_full_name'])) {
					$validationRules['full_name'] = 'trim|required|alpha_numeric_space|max_length[50]';
					$entries['full_name'] = $data['full_name'];
				}

				if (isset($data['amount'])) {
					$validationRules['amount'] = 'trim|required|numeric|greater_than[0]';
					$entries['amount'] = $data['amount'];
				}

				if (isset($data['reason'])) {
					$validationRules['reason'] = 'trim|required|alpha_numeric_punct|max_length[1000]';
					$entries['reason'] = $data['reason'];
				}

				if (isset($data['status']) && $data['status'] != '') {
					$validationRules['status'] = 'trim|required|in_list[PENDING, CANCELLED, IN PROGRESS, COMPLETED]';
					$entries['status'] = $data['status'];
				}

				// If we are not updating anything then fail!
				if (\count($validationRules) == 0) {
					return $this->failNotFound('No updatable data was provided!');
				}

				// Validate input
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Get the refund
					$refund = $this->model->getRefund($refund_id);
					if (!$refund)
						return $this->failNotFound('Refund does not exist!');

					// Update the data
					if ($this->model->updateRefund($refund_id, $entries)) {
							return $this->respond([]);
					}
					else {
						// Update failed
						return $this->failServerError();
					}
				}
				else {
					// Validation error
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

	public function addRider() {
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
					'full_name' => 'trim|required|alpha_numeric_space|max_length[50]',
					'phone'     => 'trim|required|alpha_numeric|max_length[15]|is_unique[riders.phone]',
					'email'     => 'trim|required|valid_email|max_length[50]|is_unique[riders.email]',
					'password'  => 'required',
				];

				// Validate the data
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					$password = \password_hash($data['password'], PASSWORD_DEFAULT);

					$entries = [
						'full_name' => $data['full_name'],
						'phone'     => $data['phone'],
						'email'     => $data['email'],
						'password'  => $password,
						'activated' => 1,
					];
					
					// Insert it to the database
					if ($this->model->createRider($entries)) {
						return $this->respondCreated('Rider added.');
					}
					else {
						return $this->failServerError();
					}
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

	public function addBooking() {
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
					'booking_option'       => 'trim|required|in_list[PICK_UP, DROP_OFF]',
          'receiver_name'        => 'trim|required|alpha_numeric_space|max_length[50]',
          'receiver_phone'       => 'trim|required|alpha_numeric|max_length[15]',
          'receiver_address'     => 'trim|required|alpha_numeric_punct|max_length[500]',
          'receiver_city_state'  => 'trim|required|alpha_numeric_punct|max_length[100]',
          'sender_name'          => 'trim|required|alpha_numeric_space|max_length[50]',
          'sender_phone'         => 'trim|required|alpha_numeric|max_length[15]',
          'sender_address'       => 'trim|required|alpha_numeric_punct|max_length[500]',
					'sender_city_state'    => 'trim|required|alpha_numeric_punct|max_length[100]',
					'items'                => 'required',
          'booking_type'         => 'trim|required|in_list[SAME_DAY, NEXT_DAY, EXPRESS, WAYBILL, BULK]',
					'distance_to_receiver' => 'trim|required|max_length[50]',
					'estimated_cost'       => 'trim|required|numeric|greater_than[0]',
					'payment_status'       => 'trim|required|in_list[PAID, NOT_PAID]',
				];

				if (isset($data['booking_notes']) && \trim($data['booking_notes']) != '')
					$validationRules['booking_notes'] = 'trim|alpha_numeric_punct|max_length[1000]';

				// Items image is optional
				$items_image_file = $this->request->getFile('items_image');
				if ($items_image_file)
					$validationRules['items_image'] = "uploaded[items_image]|max_size[items_image,{$max_item_image_file_size}]|ext_in[items_image,{$allowed_image_file_types}]";

				// Validate the data
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Get the items
					$items = \explode(',', $data['items']);

					// Set item validation rules
					$validationRules = [
						0 => 'trim|required|in_list[FOOD, ELECTRONICS, PHONES, OTHERS]',
						1 => 'trim|required|max_length[50]',
						2 => 'trim|required|strtolower|in_list[kg, lbs]',
					];
					$this->validation->setRules($validationRules);

					// Validate each item
					foreach ($items as $item) {
						if (!$this->validation->run(\explode('|', $item)))
							return $this->failValidationError($this->arrayToString($this->validation->getErrors()));
					}

					// Upload the items image if any
					$items_image_filename = '';
					if ($items_image_file) {
						helper('text');
						$items_images_dir = WRITEPATH . getenv('ITEMS_IMAGES_DIRECTORY');
						$items_image_filename = \random_string('alnum', 30) . $user['id'] . '.' . $items_image_file->getExtension();
						
						if (! $items_image_file->move($items_images_dir, $items_image_filename)) {
							// Failed to upload the image
							return $this->fail('Could not upload the items image.');
						}
					}

					// Add the items to the database
					$items_ids = [];
					foreach ($items as $item) {
						$values = \explode('|', $item);
						$entries = [
							'item_type'        => $values[0],
							'item_weight'      => $values[1],
							'item_weight_unit' => $values[2],
						];

						$insert_id = $this->model->addBookedItem($entries);

						if (!$insert_id)
							return $this->failServerError('Failed to add the items.');

						$items_ids[] = $insert_id;
					}

					$items_ids = \implode('|', $items_ids);

					// Create the entries array
					$entries = [
						'booking_id'          => $this->generateBookingID($admin['id']),
						'booking_option'      => $data['booking_option'],
						'receiver_name'       => $data['receiver_name'],
						'receiver_phone'      => $data['receiver_phone'],
						'receiver_address'    => $data['receiver_address'],
						'receiver_city_state' => $data['receiver_city_state'],
						'sender_name'         => $data['sender_name'],
						'sender_phone'        => $data['sender_phone'],
						'sender_address'      => $data['sender_address'],
						'sender_city_state'   => $data['sender_city_state'],
						'items_ids'           => $items_ids,
						'booking_type'        => $data['booking_type'],
						'booking_notes'       => $data['booking_notes'] ? $data['booking_notes'] : '',
						'distance_to_receiver'=> $data['distance_to_receiver'],
						'estimated_cost'      => $data['estimated_cost'],
						'user_id'             => 0,
						'payment_status'      => $data['payment_status'],
					];

					if ($items_image_file)
						$entries['items_image'] = $items_image_filename;

					// Insert the data
					$insert_id = $this->model->createBooking($entries);

					if ($insert_id) {
						return $this->respondCreated('Booking Added');
					}
					else {
						return $this->fail('Failed to add booking.');
					}
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

	public function addCourier() {
		try {
      // Set the headers
			$this->setDefaultHeaders();
			
			// Retrieve the sent data
			$data = $this->request->getJSON(true);
			if (!$data)
				return $this->failNotFound('No valid data was provided!');

			// Company validation rules
			$validationRules = [
				'name'    => 'trim|required|alpha_numeric_space|max_length[200]|is_unique[companies.name]',
				'phone'   => 'trim|required|alpha_numeric|max_length[15]|is_unique[companies.phone]',
				'email'   => 'trim|required|valid_email|max_length[50]|is_unique[companies.email]',
				'address' => 'trim|required|alpha_numeric_punct|max_length[300]',
				'reason'  => 'trim|required|alpha_numeric_punct|max_length[2000]',
			];

			// Validate the data
			$this->validation->setRules($validationRules);
			if ($this->validation->run($data)) {
				// Rider validation rules
				$validationRules = [
					'full_name' => 'trim|required|alpha_numeric_space|max_length[50]',
					'phone'     => 'trim|required|alpha_numeric|max_length[15]|is_unique[riders.phone]',
					'email'     => 'trim|required|valid_email|max_length[50]|is_unique[riders.email]',
				];

				if (! isset($data['riders']) || !$data['riders'])
					return $this->failNotFound('No rider provided for this company.');

				// Validate riders
				$riders = $data['riders'];
				$this->validation->setRules($validationRules);
				foreach($riders as $rider) {
					if (!$this->validation->run($rider))
						return $this->failValidationError($this->arrayToString($this->validation->getErrors()));
				}

				// Add the company
				$entries = [
					'name'    => $data['name'],
					'email'   => $data['email'],
					'phone'   => $data['phone'],
					'address' => $data['address'],
					'reason'  => $data['reason']
				];

				$company_id = $this->model->addCompany($entries);
				if (!$company_id)
					return $this->failServerError();
				
				// Add the riders
				$failed_riders = 0;
				foreach($riders as $rider) {
					$password = \password_hash($rider['email'], PASSWORD_DEFAULT);

					$entries = [
						'full_name'  => $rider['full_name'],
						'phone'      => $rider['phone'],
						'email'      => $rider['email'],
						'password'   => $password,
						'company_id' => $company_id,
					];
					
					// Insert it to the database
					if (!$this->model->createRider($entries))
						$failed_riders++;
				}

				if ($failed_riders == 0)
					return $this->respond(['message' => 'Registeration successful!']);
				else
					return $this->respond(['message' => 'Company added successfully but about ' . $failed_riders . ' riders were not registered. ' .
						'Please contact us to add them manually!']);
			}
			else {
				return $this->failValidationError($this->arrayToString($this->validation->getErrors()));
			}
    } catch(\Throwable $th) {
			$this->logException($th);
			return $this->failServerError();
    }
	}

	public function rejectRider($rider_id) {
		try {
      // Set the headers
      $this->setDefaultHeaders();

      $admin = $this->authenticateSession();

      if ($admin) {
				// Retrieve the sent data
				$data = $this->request->getJSON(true);

				if (! $data) {
					return $this->failNotFound('No valid data was provided!');
				}

				// Validation rules
				$validationRules = [
					'reason'   => 'trim|required|alpha_numeric_punct|max_length[2000]',
					'rider_id' => 'trim|required|numeric|is_not_unique[riders.id]',
				];

				$data['rider_id'] = $rider_id;
				// Validate input
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Get the rider
					$rider = $this->model->getRider($rider_id);
					if (!$rider)
						return $this->failNotFound('Rider does not exist!');

					// Update the data
					if ($this->model->updateRider($rider_id, ['rejected' => 1])) {
						// Email the rider the reason.
						if ($this->sendRejectionMail($rider, $data['reason']))
							return $this->respond(['message' => 'Rider rejected.']);
						else 
							return $this->respond(['message' => 'Rider rejected but no information was sent. Please manually inform the rejected rider.']);
					}
					else {
						// Update failed
						return $this->failServerError();
					}
				}
				else {
					// Validation error
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

	public function acceptRider($rider_id) {
		try {
      // Set the headers
      $this->setDefaultHeaders();

      $admin = $this->authenticateSession();

      if ($admin) {
				$data = [];

				// Validation rules
				$validationRules = [
					'rider_id' => 'trim|required|numeric|is_not_unique[riders.id]',
				];

				$data['rider_id'] = $rider_id;
				// Validate input
				$this->validation->setRules($validationRules);
				if ($this->validation->run($data)) {
					// Get the rider
					$rider = $this->model->getRider($rider_id);
					if (!$rider)
						return $this->failNotFound('Rider does not exist!');

					// Update the data
					if ($this->model->updateRider($rider_id, ['rejected' => 0, 'activated' => 1])) {
						// Email the rider.
						$this->sendAcceptanceMail($rider);
						return $this->respond(['message' => 'Rider accepted.']);
					}
					else {
						// Update failed
						return $this->failServerError();
					}
				}
				else {
					// Validation error
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

	private function sendRejectionMail($rider, $reason) {
		$subject = 'Rider Application Rejected!';
		$body = <<<_M
			Hello {$rider['full_name']},<br/><br/>

			Your application to become a 9jaDleivery rider was rejected because of the following reason(s):<br/><br/>
			<strong>{$reason}</strong>
			<br/><br/>

			If you feel we've made a mistake or you will like to try again, please contact us via info@9jadelivery.com
			with proofs and other documents that may be required to process your request again. 
_M;

		return $this->sendEmail($subject, $this->generateMessageBody($body), $rider['email']);
	}

	private function sendAcceptanceMail($rider) {
		$subject = 'Rider Application Accepted!';
		$body = <<<_M
			Hello {$rider['full_name']},<br/><br/>

			We're pleased to inform you that your application to become a 9jaDelivery Rider has been accepted.
			Please login to your account and enable your device location in order for our systems to pick your current location
			and assign appropriate jobs when possible.
_M;

		return $this->sendEmail($subject, $this->generateMessageBody($body), $rider['email']);
	}
}