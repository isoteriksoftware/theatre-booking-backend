<?php namespace App\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{
  public function getAdminData(string $username) {
    return $this->db->table('admins')
      ->where('username', $username)
      ->get()->getRowArray();
  }

  public function getRider($rider_id) {
    return $this->db->table('riders')
      ->where('id', $rider_id)
      ->get()->getRowArray();
  }

  public function updateRider($id, $entries) {
    return $this->db->table('riders')
        ->where('id', $id)
        ->update($entries);
  }

  public function getBookingData(int $id) {
    return $this->db->table('bookings')
      ->where('id', $id)
      ->get()->getRowArray();
  }

  public function getRiders($size, int $offset, $search) {
    $temp = $this->db->table('riders')
      ->select('riders.*, companies.name AS company')
      ->where('riders.rejected', 0);

    if ($search != null) {
      $temp->like('riders.full_name', $search)
        ->orLike('riders.address', $search)
        ->orLike('riders.bio', $search)
        ->orLike('riders.plate_number', $search);
    }

    return $temp
      ->join('companies', 'companies.id = riders.company_id')
      ->orderBy('date_registered', 'DESC')
      ->get($size, $offset)->getResultArray();
  }

  public function getUsers($size, int $offset, $search) {
    $temp = $this->db->table('users');

    if ($search != null) {
      $temp->like('users.full_name', $search)
        ->orLike('users.address', $search)
        ->orLike('users.bio', $search)
        ->orLike('users.company', $search);
    }

    return $temp
      ->orderBy('date_registered', 'DESC')
      ->get($size, $offset)->getResultArray();
  }

  public function getBookings($size, int $offset, $search, $status) {
    $temp = $this->db->table('bookings')
              ->select('bookings.*, users.avatar as sender_avatar');

    if ($search != null) {
      $temp->like('bookings.booking_type', $search)
        ->orLike('bookings.booking_option', str_replace(' ', '_', \strtoupper($search)))
        ->orLike('bookings.booking_type', str_replace(' ', '_', \strtoupper($search)))
        //->orLike('bookings.payment_status', str_replace(' ', '_', \strtoupper($search)), 'right')
        ->orLike('bookings.sender_name', $search)
        ->orLike('bookings.sender_address', $search)
        ->orLike('bookings.sender_city_state', $search)
        ->orLike('bookings.receiver_name', $search)
        ->orLike('bookings.receiver_address', $search)
        ->orLike('bookings.receiver_city_state', $search)
        ->orLike('bookings.estimated_cost', $search);
    }

    if ($status && $status != 'ALL')
      $temp->where('bookings.status', $status);
      
    return $temp->join('users', 'users.id = bookings.user_id')
                ->orderBy('bookings.date_added', 'DESC')
                ->get($size, $offset)->getResultArray();
  }

  public function getHoldovers($size, int $offset) {
    return $this->db->table('bookings')
      ->orderBy('date_added', 'DESC')
      ->where('status', 'HOLDOVER')
      ->get($size, $offset)->getResultArray();
  }

  public function getUserBookings(int $user_id, $size, int $offset) {
    return $this->db->table('bookings')
      ->select('bookings.*, users.avatar as sender_avatar')
      ->join('users', 'users.id = bookings.user_id')
      ->where('user_id', $user_id)
      ->orderBy('date_added', 'DESC')
      ->get($size, $offset)->getResultArray();
  }

  public function getRiderBookings(int $rider_id, $size, int $offset) {
    return $this->db->table('bookings')
      ->select('bookings.*, users.avatar as sender_avatar')
      ->join('users', 'users.id = bookings.user_id')
      ->where('rider_id', $rider_id)
      ->orderBy('date_added', 'DESC')
      ->get($size, $offset)->getResultArray();
  }

  public function getTotalRiderDeliveries(int $id) {
    return $this->db->table('deliveries')
      ->select('COUNT(id) AS total')
      ->where('deliveries.rider_id', $id)
      ->get()->getRowArray();
  }

  public function getTotalRiders() {
    return $this->db->table('riders')
      ->select('COUNT(id) AS total')
      ->get()->getRowArray();
  }

  public function getTotalUsers() {
    return $this->db->table('users')
      ->select('COUNT(id) AS total')
      ->get()->getRowArray();
  }

  public function getTotalCompanies() {
    return $this->db->table('companies')
      ->select('COUNT(id) AS total')
      ->get()->getRowArray();
  }

  public function getTotalDeliveries() {
    return $this->db->table('deliveries')
      ->select('COUNT(id) AS total')
      ->get()->getRowArray();
  }

  public function getTotalHoldovers() {
    return $this->db->table('bookings')
      ->select('COUNT(id) AS total')
      ->where('status', 'HOLDOVER')
      ->get()->getRowArray();
  }

  public function getTotalBookings() {
    return $this->db->table('bookings')
      ->select('COUNT(id) AS total')
      ->get()->getRowArray();
  }

  public function getRefunds($size, int $offset) {
    return $this->db->table('refunds')
      ->orderBy('date_added', 'DESC')
      ->get($size, $offset)->getResultArray();
  }

  public function getRiderData(int $id) {
    return $this->db->table('riders')
      ->where('id', $id)
      ->get()->getRowArray();
  }

  public function getUserData(int $id) {
    return $this->db->table('users')
      ->where('id', $id)
      ->get()->getRowArray();
  }

  public function addCoupon(array $entries) {
    if ($this->db->table('coupons')->insert($entries)) {
      return $this->insertID();
    }

    return FALSE;
  }

  public function getCouponData(int $id) {
    return $this->db->table('coupons')
      ->where('id', $id)
      ->get()->getRowArray();
  }

  public function getCoupons($size, int $offset) {
    return $this->db->table('coupons')
      ->orderBy('date_added', 'DESC')
      ->get($size, $offset)->getResultArray();
  }

  public function getFeedbacks($size, int $offset) {
    return $this->db->table('feedbacks')
      ->select('users.*, feedbacks.id AS feedback_id, feedbacks.feedback, feedbacks.date_added, feedbacks.rating')
      ->join('users', 'users.id = feedbacks.user_id')
      ->orderBy('date_added', 'DESC')
      ->get($size, $offset)->getResultArray();
  }

  public function deleteCoupon($coupon_id) {
    return $this->db->table('coupons')
      ->where('id', $coupon_id)
      ->delete();
  }

  public function getBookingsConstrainedTo(array $constraints){
    return $this->db->table('bookings')
      ->where($constraints)
      ->orderBy('date_added', 'ASC')
      ->get()->getResultArray();
  }

  public function getPrioritarizedRidersByPendingDeliveries(){
    return $this->db->table('riders')
      ->orderBy('priority', 'DESC')
      ->orderBy('pending_deliveries', 'ASC')
      ->get()->getResultArray();
  }

  public function getTopUsers() {
    return $this->db->table('users')
      ->select('users.*, COUNT(bookings.id) AS customer_bookings')
      ->join('bookings', 'users.id = bookings.user_id')
      ->groupBy('users.id')
      ->orderBy('customer_bookings', 'DESC')
      ->get()->getResultArray();
  }

  public function addRefund(array $entries) {
    if ($this->db->table('refunds')->insert($entries)) {
      return $this->insertID();
    }

    return FALSE;
  }

  public function updateRefund(int $id, array $entries) {
    return $this->db->table('refunds')
      ->where('id', $id)
      ->update($entries);
  }

  public function getRefund(int $id) {
    return $this->db->table('refunds')
      ->where('id', $id)
      ->get()->getRowArray();
  }

  public function createRider(array $entries) {
    if ($this->db->table('riders')->insert($entries)) {
      return $this->insertID();
    }

    return FALSE;
  }

  public function createBooking(array $entries) {
    if ($this->db->table('bookings')->insert($entries)) {
      return $this->insertID();
    }

    return FALSE;
  }

  public function addBookedItem(array $entries) {
    if ($this->db->table('booked_items')->insert($entries)) {
      return $this->insertID();
    }

    return FALSE;
  }

  public function addCompany(array $entries) {
    if ($this->db->table('companies')->insert($entries)) {
      return $this->insertID();
    }

    return FALSE;
  }
}