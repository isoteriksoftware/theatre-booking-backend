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
      ->select('shows.*, COUNT(tickets.id) AS tickets')
      ->orderBy('shows.date_added', 'DESC')
      ->groupBy('shows.id')
      ->join('tickets', 'tickets.show_id = shows.id', 'left')
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

  public function getTotalTickets($show_id) {
    return $this->db->table('tickets')
      ->select('COUNT(id) AS total')
      ->where('show_id', $show_id)
      ->get()->getRowArray()['total'];
  }
}