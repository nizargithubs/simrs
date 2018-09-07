<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Finance_home_c extends CI_Controller { 

	function __construct()  
	{ 
		parent::__construct();
		$sess_user = $this->session->userdata('masuk_rs');
		$id_user = $sess_user['id'];
		if($id_user == "" || $id_user == null){
	        redirect(base_url());
	    }
	}

	function index()
	{
		$data = array(
			'page' => 'finance/finance_beranda_v',
			'title' => 'Menu Setup',
			'subtitle' => 'Menu Setup',
			'master_menu' => 'home',
			'view' => ''
		);

		$this->load->view('finance/finance_home_v',$data);
	} 

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */