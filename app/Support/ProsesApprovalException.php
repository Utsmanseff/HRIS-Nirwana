<?php

nemespace App\Support;

use RuntimeException;

/** Guard approval gagal (tahap salah, status salah, jatah kurang) — pesan aman ditampilkan ke user. */
class ProsesApprovalException extends RuntimeException {}
