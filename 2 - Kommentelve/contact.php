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

	public function index()
	{
		// INITIALIZATION: GETTING THE PAGE FROM DB AND SET UP VIEW
		// ========================================================
		$page = new Custompage_Model(Custompage_Model::ID_KAPCSOLAT);
		View::title($page->title);

		// Create content view
		// Add default view variables to the content like an instance of the Input		
		$content = new View("frontend/contact");
		$content->page = $page;
		$content->error = FALSE;
		$content->input = $this->input;

		// PURIFY USER INPUT WITH HTMLPURIFIER
		$_POST = Input::instance()->xss_clean($_POST, 'htmlpurifier');

		// FORM HANDLING: IF THERE IS POSTED DATA A CONTACT E-MAIL WILL BE SENT TO THE ADMIN
		// =================================================================================
		if (isset($_POST['name']))
		{
			// Initialize validator
			$validation = new Validator($_POST, array(
				'name'		=> 'Név|required',
				'email'		=> 'E-mail|required|email',
				'message'	=> 'Üzenet|required',
			));

			// Check honeypot fields to prevent spam attacks
			// We expect three fields, one with empty value, one with a predefined
			// value and a checkbox which must be not checked!
			if (isset($_POST['legal']) || !isset($_POST['fullname']) || (trim($_POST['fullname']) != '') || !isset($_POST['address']) || (trim($_POST['address']) != 'Fake address to prevent illegal action in the form!'))
				$validation->trigger_error('Az Ön gépét rendszerünk reklámrobotnak tekintette, így kérését nem tudjuk elfogadni!');

			// Check CSRF prevention token
			if (!isset($_POST['secure_token']) || !Auth::check_csrf_token($_POST['secure_token']))
				$validation->trigger_error('Az Ön gépéhez tartozó biztonsági kulcsot hibásnak találtuk - kérjük töltse újra az oldalt!');

			// FORM VALIDATION
			// ===============

			// If the form is okay, we have to send an email to the administrator
			if ($validation->validate())
			{
				// Setup SMTP server for e-mail sending through custom server and port
				Kohana::config_set('email.options.hostname', Configurator::instance("email")->server_address);
				Kohana::config_set('email.options.port', Configurator::instance("email")->server_port);

				// Prepare contact email layout and content view
				// Add form fields to the email view
				$email_view = View::factory('frontend/emails/layout');
				$email_view->content = View::factory('frontend/emails/contact');
				$email_view->content->name = $this->input->post('name');
				$email_view->content->email = $this->input->post('email');
				$email_view->content->message = nl2br(strip_tags($this->input->post('message')));

				// Send e-mail to the administrator
				email::send(
					Configurator::instance("contact")->email,
					'noreply@refactorteszt.hu',
					'[refactor teszt] Ez egy e-mail a kapcsolati űrlapról!',
					$email_view->render(),
					TRUE
				);

				// Redirect user back to the original form through a notification page
				$this->javascript_redirect($page->url, 'Köszönjük üzenetét, hamarosan válaszolunk rá!');
			}

			// If there is an error we have to report it back to the user
			// and do not handle the form
			else
			{
				$content->error = $validation->errors('p');
			}
		}

		// RENDER VIEW
		// ===========
		$layout = new View('frontend/layout');
		$layout->content = $content;
		$layout->render(TRUE);
	}


}