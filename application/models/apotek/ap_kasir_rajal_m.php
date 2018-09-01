<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ap_kasir_rajal_m extends CI_Model {

	function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	function get_pasien($tanggal,$keyword){
		$where = "1 = 1";

		if($keyword != ""){
			$where = $where." AND (a.NAMA LIKE '%$keyword%' OR a.NAMA_ORTU LIKE '%$keyword%')";
		}else{
			$where = $where;
		}

		$sql = "SELECT
							a.*,
							(a.TOT_POLI+a.TOT_TINDAKAN+a.TOT_RESEP+a.TOT_LAB) AS TOTAL
						FROM(
							SELECT
								RJ.ID,
								RJ.ID_PASIEN,
								PS.NAMA,
								IFNULL(PS.NAMA_ORTU,'-') AS NAMA_ORTU,
								RJ.TANGGAL,
								RJ.ID_POLI,
								IFNULL(PL.NAMA,'-') AS NAMA_POLI,
								PEG.NAMA AS NAMA_PEGAWAI,
								RJ.STS_BAYAR,
								IFNULL(PL.BIAYA,0) AS TOT_POLI,
								IFNULL(TD.TOTAL,0) AS TOT_TINDAKAN,
								IFNULL(RS.TOTAL,0) AS TOT_RESEP,
								IFNULL(LAB.TOTAL_TARIF,0) AS TOT_LAB,
								RS.KODE_RESEP,
								PEM.ID AS ID_KASIR_RAJAL,
								RJ.STS_CLOSING
							FROM admum_rawat_jalan RJ
							LEFT JOIN rk_pasien PS ON PS.ID = RJ.ID_PASIEN
							LEFT JOIN (
								SELECT * FROM admum_poli
								WHERE AKTIF = '1'
							) PL ON PL.ID = RJ.ID_POLI
							LEFT JOIN kepeg_pegawai PEG ON PEG.ID = PL.ID_PEG_DOKTER
							LEFT JOIN rk_tindakan_rj TD ON TD.ID_PELAYANAN = RJ.ID
							LEFT JOIN rk_resep_rj RS ON RS.ID_PELAYANAN = RJ.ID
							LEFT JOIN rk_laborat_rj LAB ON LAB.ID_PELAYANAN = RJ.ID
							LEFT JOIN rk_pembayaran_kasir PEM ON RJ.ID = PEM.ID_PELAYANAN
						) a
						WHERE $where
						AND a.TANGGAL = '$tanggal'
						AND a.STS_CLOSING = '0'
						ORDER BY a.ID DESC
					";
		$query = $this->db->query($sql);
		return $query->result();
	}

	function get_poli_by_rj($id){
		$sql = "
			SELECT
				RJ.*,
				PS.NAMA AS NAMA_PASIEN,
				P.ID AS ID_POLI,
				P.NAMA AS NAMA_POLI,
				P.BIAYA,
				PEG.NAMA AS NAMA_DOKTER
			FROM admum_rawat_jalan RJ
			LEFT JOIN admum_poli P ON P.ID = RJ.ID_POLI
			LEFT JOIN kepeg_pegawai PEG ON PEG.ID = P.ID_PEG_DOKTER
			LEFT JOIN rk_pasien PS ON PS.ID = RJ.ID_PASIEN
			WHERE RJ.ID = '$id'
		";
		$query = $this->db->query($sql);
		return $query->row();
	}

	function get_tindakan_det($id_tindakan){
		$sql = "
			SELECT
				DET.*,
				TD.NAMA_TINDAKAN,
				TD.TARIF
			FROM rk_tindakan_rj_detail DET
			LEFT JOIN admum_setup_tindakan TD ON TD.ID = DET.TINDAKAN
			WHERE DET.ID_TINDAKAN_RJ = '$id_tindakan'
		";
		$query = $this->db->query($sql);
		return $query->result();
	}

	function get_resep2($id_resep){
		$sql = "SELECT
							DET.ID,
							NM_OBT.KODE_OBAT,
							NM_OBT.NAMA_OBAT,
							DET.TAKARAN,
							DET.ATURAN_MINUM,
							DET.HARGA,
							DET.JUMLAH_BELI,
							DET.SUBTOTAL
						FROM rk_resep_detail_rj DET
						LEFT JOIN apotek_gudang_obat GD ON GD.ID = DET.ID_OBAT
						LEFT JOIN admum_setup_nama_obat NM_OBT ON NM_OBT.ID = GD.ID_SETUP_NAMA_OBAT
						WHERE DET.ID_RESEP = '$id_resep'
					";
		$query = $this->db->query($sql);
		return $query->result();
	}

	function get_laborat($id_laborat){
		$sql = "SELECT
							DET.ID,
							SP.NAMA_PEMERIKSAAN,
							SP.TARIF,
							LB.ID AS ID_LABORAT,
							LB.TOTAL_TARIF
						FROM rk_laborat_rj_detail DET
						LEFT JOIN admum_setup_pemeriksaan SP ON SP.ID = DET.PEMERIKSAAN
						LEFT JOIN rk_laborat_rj LB ON LB.ID = DET.ID_PEMERIKSAAN_RJ
						WHERE DET.ID_PEMERIKSAAN_RJ = '$id_laborat'
					";
		$query = $this->db->query($sql);
		return $query->result();
	}

	function data_obat($keyword){
		$where = "1 = 1";

		if($keyword != ""){
			$where = $where." AND (NM_OBT.NAMA_OBAT LIKE '%$keyword%' OR NM_OBT.BARCODE LIKE '%$keyword%' OR NM_OBT.KODE_OBAT LIKE '%$keyword%')";
		}

		$sql = "
			SELECT
				OBAT.ID,
				NM_OBT.KODE_OBAT,
				NM_OBT.BARCODE,
				NM_OBT.NAMA_OBAT,
				SUP.MERK,
				JENIS.NAMA_JENIS,
				SAT.NAMA_SATUAN,
				OBAT.JUMLAH,
				OBAT.ISI,
				OBAT.TOTAL,
				OBAT.SATUAN_ISI,
				OBAT.JUMLAH_BUTIR,
				OBAT.SATUAN_BUTIR,
				OBAT.HARGA_BELI,
				OBAT.HARGA_JUAL,
				OBAT.KADALUARSA,
				OBAT.TANGGAL_MASUK,
				OBAT.WAKTU_MASUK,
				OBAT.AKTIF,
				OBAT.URUT_BARANG
			FROM apotek_gudang_obat OBAT
			LEFT JOIN admum_setup_nama_obat NM_OBT ON NM_OBT.ID = OBAT.ID_SETUP_NAMA_OBAT
			LEFT JOIN obat_supplier SUP ON SUP.ID = NM_OBT.ID_MERK
			LEFT JOIN obat_jenis JENIS ON JENIS.ID = OBAT.ID_JENIS_OBAT
			LEFT JOIN obat_satuan SAT ON SAT.ID = OBAT.ID_SATUAN_OBAT
			WHERE $where
			AND OBAT.AKTIF = '1'
		";
		$query = $this->db->query($sql);
		return $query->result();
	}

	function data_obat_id($id){
		$sql = "
			SELECT
				OBAT.ID,
				OBAT.ID_SETUP_NAMA_OBAT,
				NM_OBT.KODE_OBAT,
				NM_OBT.BARCODE,
				NM_OBT.NAMA_OBAT,
				NM_OBT.ID_MERK,
				SUP.MERK,
				OBAT.ID_JENIS_OBAT,
				JENIS.NAMA_JENIS,
				OBAT.ID_SATUAN_OBAT,
				SAT.NAMA_SATUAN,
				OBAT.JUMLAH,
				OBAT.ISI,
				OBAT.TOTAL,
				OBAT.SATUAN_ISI,
				OBAT.JUMLAH_BUTIR,
				OBAT.SATUAN_BUTIR,
				OBAT.HARGA_BELI,
				OBAT.HARGA_JUAL,
				OBAT.KADALUARSA,
				OBAT.TANGGAL_MASUK
			FROM apotek_gudang_obat OBAT
			LEFT JOIN admum_setup_nama_obat NM_OBT ON NM_OBT.ID = OBAT.ID_SETUP_NAMA_OBAT
			LEFT JOIN obat_supplier SUP ON SUP.ID = NM_OBT.ID_MERK
			LEFT JOIN obat_jenis JENIS ON JENIS.ID = OBAT.ID_JENIS_OBAT
			LEFT JOIN obat_satuan SAT ON SAT.ID = OBAT.ID_SATUAN_OBAT
			WHERE OBAT.ID = '$id'
		";
		$query = $this->db->query($sql);
		return $query->result();
	}

	function get_user_detail($id_user){
		$sql = "
			SELECT
				a.*
			FROM kepeg_pegawai a
			WHERE a.ID = '$id_user'
		";

		return $this->db->query($sql)->row();
	}

	function get_jenis_obat(){
		$sql = "SELECT * FROM obat_jenis ORDER BY ID ASC LIMIT 5";

		return $this->db->query($sql)->result();
	}

	function get_all_obat(){
		$sql = "
			SELECT
				OBAT.ID,
				OBAT.ID_SETUP_NAMA_OBAT,
				NM_OB.KODE_OBAT,
				NM_OB.BARCODE,
				NM_OB.NAMA_OBAT,
				OBAT.ID_JENIS_OBAT,
				JNS.NAMA_JENIS,
				OBAT.ID_SATUAN_OBAT,
				SAT.KODE_SATUAN,
				SAT.NAMA_SATUAN,
				OBAT.JUMLAH,
				OBAT.ISI,
				OBAT.TOTAL,
				OBAT.SATUAN_ISI,
				OBAT.JUMLAH_BUTIR,
				OBAT.SATUAN_BUTIR,
				OBAT.HARGA_JUAL,
				OBAT.GAMBAR
			FROM apotek_gudang_obat OBAT
			LEFT JOIN admum_setup_nama_obat NM_OB ON NM_OB.ID = OBAT.ID_SETUP_NAMA_OBAT
			LEFT JOIN obat_jenis JNS ON JNS.ID = OBAT.ID_JENIS_OBAT
			LEFT JOIN obat_satuan SAT ON SAT.ID = OBAT.ID_SATUAN_OBAT
			ORDER BY OBAT.ID DESC
		";
		return $this->db->query($sql)->result();
	}

	function simpan_trx($invoice,$tanggal,$bulan,$tahun,$waktu,$atas_nama,$diskon,$ppn,$total,$bayar,$kembali,$jenis_bayar){
		$sql = "
			INSERT INTO apotek_transaksi(
				INVOICE,
				TANGGAL,
				BULAN,
				TAHUN,
				WAKTU,
				ATAS_NAMA,
				DISKON,
				PPN,
				TOTAL,
				BAYAR,
				KEMBALI,
				JENIS_BAYAR
			) VALUES (
				'$invoice',
				'$tanggal',
				'$bulan',
				'$tahun',
				'$waktu',
				'$atas_nama',
				'$diskon',
				'$ppn',
				'$total',
				'$bayar',
				'$kembali',
				'$jenis_bayar'
			)
		";
		$this->db->query($sql);
	}

	function simpan_trx_kredit($invoice,$tanggal,$bulan,$tahun,$waktu,$atas_nama,$diskon,$ppn,$total,$bayar,$kembali,$jenis_bayar,$kartu_kredit,$nomor_kartu){
		$sql = "
			INSERT INTO apotek_transaksi(
				INVOICE,
				TANGGAL,
				BULAN,
				TAHUN,
				WAKTU,
				ATAS_NAMA,
				DISKON,
				PPN,
				TOTAL,
				BAYAR,
				KEMBALI,
				JENIS_BAYAR,
				KARTU_KREDIT,
				NOMOR_KARTU
			) VALUES (
				'$invoice',
				'$tanggal',
				'$bulan',
				'$tahun',
				'$waktu',
				'$atas_nama',
				'$diskon',
				'$ppn',
				'$total',
				'$bayar',
				'$kembali',
				'$jenis_bayar',
				'$kartu_kredit',
				'$nomor_kartu'
			)
		";
		$this->db->query($sql);
	}

	function simpan_det_trx($id_transaksi,$id_obat,$harga,$jumlah_beli,$subtotal){
		$sql = "
			INSERT INTO apotek_transaksi_detail(
				ID_TRANSAKSI,
				ID_OBAT,
				HARGA,
				JUMLAH_BELI,
				SUBTOTAL
			) VALUES (
				'$id_transaksi',
				'$id_obat',
				'$harga',
				'$jumlah_beli',
				'$subtotal'
			)
		";
		$this->db->query($sql);
	}

	function simpan_pembayaran($invoice,
															$id_rj,
															$id_pasien,
															$id_poli,
															$id_pegawai,
															$shift,
															$tanggal,
															$waktu,
															$biaya_poli,
															$biaya_tindakan,
															$biaya_resep,
															$biaya_lab,
															$total,
															$jenis_pembayaran,
															$bayar,
															$kartu_provider,
															$no_kartu,
															$tambahan){
		$sql = "INSERT INTO rk_pembayaran_kasir(
							INVOICE,
							ID_PELAYANAN,
							ID_PASIEN,
							ID_POLI,
							ID_PEGAWAI,
							SHIFT,
							TANGGAL,
							WAKTU,
							BIAYA_POLI,
							BIAYA_TINDAKAN,
							BIAYA_RESEP,
							BIAYA_LAB,
							TOTAL,
							JENIS_PEMBAYARAN,
							TIPE,
							BAYAR,
							KARTU_PROVIDER,
							NO_KARTU,
							TAMBAHAN
						) VALUES (
							'$invoice',
							'$id_rj',
							'$id_pasien',
							'$id_poli',
							'$id_pegawai',
							'$shift',
							'$tanggal',
							'$waktu',
							'$biaya_poli',
							'$biaya_tindakan',
							'$biaya_resep',
							'$biaya_lab',
							'$total',
							'$jenis_pembayaran',
							'RJ',
							'$bayar',
							'$kartu_provider',
							'$no_kartu',
							'$tambahan'
						)
					";
		$this->db->query($sql);

		$data_update = array(
			'TOTAL' => $biaya_resep
		);

		$this->db->where('ID_PELAYANAN', $id_rj);
    $this->db->update('rk_resep_rj', $data_update);
	}

	function struk_resep($id_rj){
		$sql = $this->db->query("SELECT
															RJ.ID,
															RJ.ID_PASIEN,
															PS.NAMA,
															PS.UMUR,
															PS.ALAMAT AS ALAMAT_PASIEN,
															PS.KODE_PASIEN,
															PS.TELEPON AS TELEPON_PASIEN,
															IFNULL(PS.NAMA_ORTU,'-') AS NAMA_ORTU,
															RJ.TANGGAL,
															RJ.ID_POLI,
															IFNULL(PL.NAMA,'-') AS NAMA_POLI,
															PEG.NAMA AS NAMA_PEGAWAI,
															RJ.STS_BAYAR,
															IFNULL(PL.BIAYA,0) AS TOT_POLI,
															IFNULL(TD.TOTAL,0) AS TOT_TINDAKAN,
															IFNULL(RS.TOTAL,0) AS TOT_RESEP,
															IFNULL(LAB.TOTAL_TARIF,0) AS TOT_LAB,
															RS.ID AS ID_RESEP,
															RS.ALERGI_OBAT,
															RS.KODE_RESEP
														FROM admum_rawat_jalan RJ
														LEFT JOIN rk_pasien PS ON PS.ID = RJ.ID_PASIEN
														LEFT JOIN (
															SELECT * FROM admum_poli
															WHERE AKTIF = '1'
														) PL ON PL.ID = RJ.ID_POLI
														LEFT JOIN kepeg_pegawai PEG ON PEG.ID = PL.ID_PEG_DOKTER
														LEFT JOIN rk_tindakan_rj TD ON TD.ID_PELAYANAN = RJ.ID
														LEFT JOIN rk_resep_rj RS ON RS.ID_PELAYANAN = RJ.ID
														LEFT JOIN rk_laborat_rj LAB ON LAB.ID_PELAYANAN = RJ.ID
													  	WHERE RJ.ID = '$id_rj'
		");

		return $sql->row_array();
	}

	function detail_resep($id_resep){
		$sql = "SELECT
							DET.ID,
							NM_OBT.KODE_OBAT,
							NM_OBT.NAMA_OBAT,
							DET.TAKARAN,
							DET.ATURAN_MINUM,
							DET.HARGA,
							DET.JUMLAH_BELI,
							DET.SUBTOTAL
						FROM rk_resep_detail_rj DET
						LEFT JOIN apotek_gudang_obat GD ON GD.ID = DET.ID_OBAT
						LEFT JOIN admum_setup_nama_obat NM_OBT ON NM_OBT.ID = GD.ID_SETUP_NAMA_OBAT
						WHERE DET.ID_RESEP = '$id_resep'
					";
		return $this->db->query($sql)->result();
	}

	function simpan_closing($id_rajal, $id_pegawai, $shift, $tanggal, $pukul){
		$data = array(
			'ID_KASIR_RAJAL' => $id_rajal,
			'TANGGAL' => $tanggal,
			'WAKTU' => $pukul,
			'ID_PEGAWAI' => $id_pegawai,
			'SHIFT' => $shift
		);

	  $this->db->insert('ap_tutup_kasir_rajal', $data);

		$data_update = array(
			'STATUS_CLOSING' => 1
		);
		$this->db->where('ID', $id_rajal);
    $this->db->update('rk_pembayaran_kasir', $data_update);

		$this->db->select('*');
		$this->db->from('rk_pembayaran_kasir');
		$this->db->where('ID', $id_rajal);
		$row_rajal = $this->db->get()->row_array();

		$id_pelayanan = $row_rajal['ID_PELAYANAN'];

		$data_update_rajal = array(
			'STS_CLOSING' => 1
		);
		$this->db->where('ID', $id_pelayanan);
    $this->db->update('admum_rawat_jalan', $data_update_rajal);
	}

	function data_pembayaran(){
		$query = $this->db->query("SELECT
																TUTUP.ID AS ID_CLOSING,
																TUTUP.TANGGAL AS TANGGAL_CLOSING,
																TUTUP.SHIFT,
																BAYAR.INVOICE,
																RAJAL.ID AS ID_RAJAL,
																BAYAR.TOTAL,
																PEGAWAI.NAMA AS NAMA_PEGAWAI,
																RESEP.KODE_RESEP
																FROM
																ap_tutup_kasir_rajal AS TUTUP
																LEFT JOIN rk_pembayaran_kasir AS BAYAR ON TUTUP.ID_KASIR_RAJAL=BAYAR.ID
																LEFT JOIN rk_pasien AS PASIEN ON BAYAR.ID_PASIEN=PASIEN.ID
																LEFT JOIN admum_rawat_jalan AS RAJAL ON BAYAR.ID_PELAYANAN=RAJAL.ID
																LEFT JOIN rk_resep_rj RESEP ON RESEP.ID_PELAYANAN = RAJAL.ID
																LEFT JOIN kepeg_pegawai AS PEGAWAI ON TUTUP.ID_PEGAWAI=PEGAWAI.ID
		");
		return $query->result_array();
	}

	function data_rekap_pendapatan(){
		$query = $this->db->query("SELECT
																PEM.ID,
																PEM.TANGGAL,
																PEM.SHIFT,
																PEM.INVOICE,
																PEM.TOTAL,
																RAJAL.ID AS ID_RAJAL,
																PEGAWAI.NAMA AS NAMA_PEGAWAI,
																RESEP.KODE_RESEP,
																POLI.NAMA AS NAMA_POLI
																FROM
																rk_pembayaran_kasir AS PEM
																LEFT JOIN rk_pasien AS PASIEN ON PEM.ID_PASIEN=PASIEN.ID
																LEFT JOIN admum_rawat_jalan AS RAJAL ON PEM.ID_PELAYANAN=RAJAL.ID
																LEFT JOIN rk_resep_rj RESEP ON RESEP.ID_PELAYANAN=RAJAL.ID
																LEFT JOIN kepeg_pegawai AS PEGAWAI ON PEM.ID_PEGAWAI=PEGAWAI.ID
																LEFT JOIN admum_poli AS POLI ON PEM.ID_POLI=POLI.ID
		");
		return $query->result_array();
	}

	function tanggal_filter($tanggal_sekarang, $tanggal_sampai){
		$query = $this->db->query("SELECT
																PEM.ID,
																PEM.TANGGAL,
																PEM.SHIFT,
																PEM.INVOICE,
																PEM.TOTAL,
																RAJAL.ID AS ID_RAJAL,
																PEGAWAI.NAMA AS NAMA_PEGAWAI,
																RESEP.KODE_RESEP,
																POLI.NAMA AS NAMA_POLI
																FROM
																rk_pembayaran_kasir AS PEM
																LEFT JOIN rk_pasien AS PASIEN ON PEM.ID_PASIEN=PASIEN.ID
																LEFT JOIN admum_rawat_jalan AS RAJAL ON PEM.ID_PELAYANAN=RAJAL.ID
																LEFT JOIN rk_resep_rj RESEP ON RESEP.ID_PELAYANAN=RAJAL.ID
																LEFT JOIN kepeg_pegawai AS PEGAWAI ON PEM.ID_PEGAWAI=PEGAWAI.ID
																LEFT JOIN admum_poli AS POLI ON PEM.ID_POLI=POLI.ID
																WHERE STR_TO_DATE(PEM.TANGGAL,'%d-%m-%Y') >= STR_TO_DATE('$tanggal_sekarang','%d-%m-%Y')
										            AND STR_TO_DATE(PEM.TANGGAL,'%d-%m-%Y') <= STR_TO_DATE('$tanggal_sampai','%d-%m-%Y')
		");
		return $query->result_array();
	}

	function poli_filter($id_poli){
		$query = $this->db->query("SELECT
																PEM.ID,
																PEM.TANGGAL,
																PEM.SHIFT,
																PEM.INVOICE,
																PEM.TOTAL,
																RAJAL.ID AS ID_RAJAL,
																PEGAWAI.NAMA AS NAMA_PEGAWAI,
																RESEP.KODE_RESEP,
																POLI.NAMA AS NAMA_POLI
																FROM
																rk_pembayaran_kasir AS PEM
																LEFT JOIN rk_pasien AS PASIEN ON PEM.ID_PASIEN=PASIEN.ID
																LEFT JOIN admum_rawat_jalan AS RAJAL ON PEM.ID_PELAYANAN=RAJAL.ID
																LEFT JOIN rk_resep_rj RESEP ON RESEP.ID_PELAYANAN=RAJAL.ID
																LEFT JOIN kepeg_pegawai AS PEGAWAI ON PEM.ID_PEGAWAI=PEGAWAI.ID
																LEFT JOIN admum_poli AS POLI ON PEM.ID_POLI=POLI.ID
																WHERE POLI.ID = '$id_poli'
		");
		return $query->result_array();
	}

	function nota_poli($id_rj){
		$sql = $this->db->query("SELECT
															a.ID_PELAYANAN,
															a.INVOICE,
															a.TANGGAL,
															a.SHIFT,
															a.JENIS_PEMBAYARAN,
															a.TOTAL,
															b.NAMA AS NAMA_PASIEN,
															b.ALAMAT AS ALAMAT_PASIEN,
															d.TAKARAN,
															d.JUMLAH_BELI,
															d.ID_RESEP,
															e.NAMA_OBAT,
															f.NAMA AS NAMA_PEGAWAI,
															g.NAMA AS NAMA_POLI,
															h.NAMA AS NAMA_DOKTER
															FROM
															rk_pembayaran_kasir a
															LEFT JOIN rk_pasien b ON a.ID_PASIEN = b.ID
															LEFT JOIN rk_resep_rj c ON a.ID_PELAYANAN = c.ID_PELAYANAN
															LEFT JOIN rk_resep_detail_rj d ON c.ID = d.ID_RESEP
															LEFT JOIN admum_setup_nama_obat e ON d.ID_OBAT = e.ID
															LEFT JOIN kepeg_pegawai f ON a.ID_PEGAWAI = f.ID
															LEFT JOIN admum_poli g ON a.ID_POLI = g.ID
															LEFT JOIN kepeg_pegawai h ON h.ID = g.ID_PEG_DOKTER
															WHERE a.ID_PELAYANAN = '$id_rj'
		");

		return $sql->row_array();
	}

	function struk_pembayaran($id_rj){
		$sql = $this->db->query("SELECT
															a.INVOICE,
															a.TANGGAL,
															a.SHIFT,
															a.JENIS_PEMBAYARAN,
															a.TOTAL,
															b.NAMA AS NAMA_PASIEN,
															b.ALAMAT AS ALAMAT_PASIEN,
															d.TAKARAN,
															d.JUMLAH_BELI,
															d.ID_RESEP,
															e.NAMA_OBAT,
															f.NAMA AS NAMA_PEGAWAI,
															g.NAMA AS NAMA_POLI,
															h.NAMA AS NAMA_DOKTER
															FROM
															rk_pembayaran_kasir a
															LEFT JOIN rk_pasien b ON a.ID_PASIEN = b.ID
															LEFT JOIN rk_resep_rj c ON a.ID_PELAYANAN = c.ID_PELAYANAN
															LEFT JOIN rk_resep_detail_rj d ON c.ID = d.ID_RESEP
															LEFT JOIN admum_setup_nama_obat e ON d.ID_OBAT = e.ID
															LEFT JOIN kepeg_pegawai f ON a.ID_PEGAWAI = f.ID
															LEFT JOIN admum_poli g ON a.ID_POLI = g.ID
															LEFT JOIN kepeg_pegawai h ON h.ID = g.ID_PEG_DOKTER
															WHERE a.ID_PELAYANAN = '$id_rj'
		");

		return $sql->row_array();
	}

	function data_poli(){
		$sql = $this->db->query("SELECT a.ID AS id_poli, a.NAMA AS nama_poli FROM admum_poli a");
		return $sql->result_array();
	}

	function print_pdf(
		$by,
		$tanggal_sekarang,
		$tanggal_sampai,
		$id_poli
	){
		$where = '1=1';
		if ($by == 'semua'){
		}elseif ($by == 'tanggal') {
			$where = $where."  AND STR_TO_DATE(PEM.TANGGAL,'%d-%m-%Y') >= STR_TO_DATE('$tanggal_sekarang','%d-%m-%Y')
												AND STR_TO_DATE(PEM.TANGGAL,'%d-%m-%Y') <= STR_TO_DATE('$tanggal_sampai','%d-%m-%Y') ";
		}elseif ($by == 'poli') {
			$where = $where." AND POLI.ID = '$id_poli' ";
		}
		$sql = "SELECT
						POLI.NAMA AS NAMA_POLI,
						SUM(PEM.BIAYA_POLI) AS TOTAL_POLI
						FROM
						rk_pembayaran_kasir AS PEM
						LEFT JOIN rk_pasien AS PASIEN ON PEM.ID_PASIEN=PASIEN.ID
						LEFT JOIN admum_rawat_jalan AS RAJAL ON PEM.ID_PELAYANAN=RAJAL.ID
						LEFT JOIN rk_resep_rj RESEP ON RESEP.ID_PELAYANAN=RAJAL.ID
						LEFT JOIN kepeg_pegawai AS PEGAWAI ON PEM.ID_PEGAWAI=PEGAWAI.ID
						LEFT JOIN admum_poli AS POLI ON PEM.ID_POLI=POLI.ID
						WHERE $where
						GROUP BY PEM.ID_POLI
					";
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	function print_pdf_row(
		$by,
		$tanggal_sekarang,
		$tanggal_sampai,
		$id_poli
	){
		$where = '1=1';
		if ($by == 'semua'){
		}elseif ($by == 'tanggal') {
			$where = $where."  AND STR_TO_DATE(PEM.TANGGAL,'%d-%m-%Y') >= STR_TO_DATE('$tanggal_sekarang','%d-%m-%Y')
												AND STR_TO_DATE(PEM.TANGGAL,'%d-%m-%Y') <= STR_TO_DATE('$tanggal_sampai','%d-%m-%Y') ";
		}elseif ($by == 'poli') {
			$where = $where." AND POLI.ID = '$id_poli' ";
		}
		$sql = "SELECT
						PEM.ID,
						PEM.TANGGAL,
						PEM.SHIFT,
						PEM.INVOICE,
						PEM.TOTAL,
						RAJAL.ID AS ID_RAJAL,
						PEGAWAI.NAMA AS NAMA_PEGAWAI,
						RESEP.KODE_RESEP,
						POLI.NAMA AS NAMA_POLI
						FROM
						rk_pembayaran_kasir AS PEM
						LEFT JOIN rk_pasien AS PASIEN ON PEM.ID_PASIEN=PASIEN.ID
						LEFT JOIN admum_rawat_jalan AS RAJAL ON PEM.ID_PELAYANAN=RAJAL.ID
						LEFT JOIN rk_resep_rj RESEP ON RESEP.ID_PELAYANAN=RAJAL.ID
						LEFT JOIN kepeg_pegawai AS PEGAWAI ON PEM.ID_PEGAWAI=PEGAWAI.ID
						LEFT JOIN admum_poli AS POLI ON PEM.ID_POLI=POLI.ID
						WHERE $where
					";
		$query = $this->db->query($sql);
		return $query->row_array();
	}
}
