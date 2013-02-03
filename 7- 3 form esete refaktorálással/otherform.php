<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 *
 *
 * @package	   Controllers
 * @author     Andrew "Drawain" Fincza
 * @copyright  (c) 2008-2009 Drawain
 * @license    http://www.drawain.hu/license.html
 */


class Otherform_Controller extends Form_Controller {

	public function  __construct()
	{
		$this->content_view_path = "frontend/otherform";
		$this->page = new Custompage_Model(Custompage_Model::ID_MASIK);

		$this->validation_rules = array(
				'name'		=> 'Név|required',
				'email'		=> 'E-mail|required|email',
				'addr'		=> 'Cím|required',
				'gender'	=> 'Nem|required|exact[Nő,Férfi]',
				'message'	=> 'Üzenet|required',
		);

		$this->email_address_to = Configurator::instance("contact")->email;
		$this->email_subject = '[refactor teszt] Ez egy e-mail a másik formról!';
		$this->message_on_form_success = 'Köszönjük üzenetét, hamarosan válaszolunk rá!';

		parent::__construct();
	}

	protected function get_email_view()
	{
		$email_view = View::factory('frontend/emails/layout');
		$email_view->content = View::factory('frontend/emails/otherform');
		$email_view->content->name = $this->input->post('name');
		$email_view->content->email = $this->input->post('email');
		$email_view->content->gender = $this->input->post('gender');
		$email_view->content->address = $this->input->post('addr');
		$email_view->content->message = nl2br(strip_tags($this->input->post('message')));
		return $email_view;
	}

}