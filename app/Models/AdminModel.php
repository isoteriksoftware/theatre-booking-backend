<?php namespace App\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{
  public function getAdminData(string $username) {
    return $this->db->table('admins')
      ->where('username', $username)
      ->get()->getRowArray();
  }

  public function getBookingData(int $id) {
    return $this->db->table('bookings')
      ->where('id', $id)
      ->get()->getRowArray();
  }

  public function addTicket(array $entries) {
    if ($this->db->table('tickets')->insert($entries)) {
      return $this->insertID();
    }

    return FALSE;
  }

  public function addShow(array $entries) {
    if ($this->db->table('shows')->insert($entries)) {
      return $this->insertID();
    }

    return FALSE;
  }

  public function addBooking(array $entries) {
    if ($this->db->table('bookings')->insert($entries)) {
      return $this->insertID();
    }

    return FALSE;
  }

  public function getTickets() {
    return $this->db->table('tickets')
      ->get()->getResultArray();
  }

  public function getShows() {
    return $this->db->table('shows')
      ->get()->getResultArray();
  }

  public function getBookings() {
    return $this->db->table('bookings')
      ->get()->getResultArray();
  }

  public function getTicket(int $id) {
    return $this->db->table('tickets')
      ->where('id', $id)
      ->get()->getRowArray();
  }

  public function getShow(int $id) {
    return $this->db->table('shows')
      ->where('id', $id)
      ->get()->getRowArray();
  }
}