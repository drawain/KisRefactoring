<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 *
 *
 * @package	   Controllers
 * @author     Andrew "Drawain" Fincza
 * @copyright  (c) 2008-2009 Drawain
 * @license    http://www.drawain.hu/license.html
 */


class Contact_Controller extends Controller {

	/**
	 * @var Custompage_Model
	 */
	protected $page;

	/**
	 * @var View
	 */
	protected $content;

	public function index()
	{
		$this->page = new Custompage_Model(Custompage_Model::ID_KAPCSOLAT);
		View::title($this->page->title);

		$this->content = new View("frontend/contact");
		$this->content->page = $this->page;
		$this->content->error = FALSE;
		$this->content->input = $this->input;

		$_POST = Input::instance()->xss_clean($_POST, 'htmlpurifier');

		if (isset($_POST['name']))
		{
			$validation = new Validator($_POST, array(
				'name'		=> 'Név|required',
				'email'		=> 'E-mail|required|email',
				'message'	=> 'Üzenet|required',
			));

			if (isset($_POST['legal']) || !isset($_POST['fullname']) || (trim($_POST['fullname']) != '') || !isset($_POST['address']) || (trim($_POST['address']) != 'Fake address to prevent illegal action in the form!'))
				$validation->trigger_error('Az Ön gépét rendszerünk reklámrobotnak tekintette, így kérését nem tudjuk elfogadni!');

			if (!isset($_POST['secure_token']) || !Auth::check_csrf_token($_POST['secure_token']))
				$validation->trigger_error('Az Ön gépéhez tartozó biztonsági kulcsot hibásnak találtuk - kérjük töltse újra az oldalt!');

			if ($validation->validate())
			{
				Kohana::config_set('email.options.hostname', Configurator::instance("email")->server_address);
				Kohana::config_set('email.options.port', Configurator::instance("email")->server_port);

				$email_view = View::factory('frontend/emails/layout');
				$email_view->content = View::factory('frontend/emails/contact');
				$email_view->content->name = $this->input->post('name');
				$email_view->content->email = $this->input->post('email');
				$email_view->content->message = nl2br(strip_tags($this->input->post('message')));

				email::send(
					Configurator::instance("contact")->email,
					'noreply@refactorteszt.hu',
					'[refactor teszt] Ez egy e-mail a kapcsolati űrlapról!',
					$email_view->render(),
					TRUE
				);

				$this->javascript_redirect($this->page->url, 'Köszönjük üzenetét, hamarosan válaszolunk rá!');
			}
			else
			{
				$this->content->error = $validation->errors('p');
			}
		}

		$layout = new View('frontend/layout');
		$layout->content = $this->content;
		$layout->render(TRUE);
	}


}