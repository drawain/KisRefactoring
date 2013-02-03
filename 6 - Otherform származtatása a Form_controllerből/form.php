<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 *
 *
 * @package	   Controllers
 * @author     Andrew "Drawain" Fincza
 * @copyright  (c) 2008-2009 Drawain
 * @license    http://www.drawain.hu/license.html
 */


abstract class Form_Controller extends Controller {

	protected $is_form_success = FALSE;

	protected $email_subject;
	protected $email_address_to;
	protected $validation_rules;
	protected $content_view_path;
	protected $message_on_form_success;

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
		$this->initialize_content();
		$this->initialize_and_handle_form();

		if ($this->is_form_success) {
			$this->redirect_to_form_success();
		} else {
			$this->render_content();
		}
	}

	protected function redirect_to_form_success()
	{
		$this->javascript_redirect($this->page->url, $this->message_on_form_success);
	}

	protected function render_content()
	{
		$layout = new View('frontend/layout');
		$layout->content = $this->content;
		$layout->render(TRUE);
	}

	protected function initialize_and_handle_form()
	{
		$this->initialize_form();

		if ($this->is_posted_data()) {
			$this->handle_form();
		}
	}

	protected function initialize_form()
	{
		$this->purify_user_input();
		$this->content->error = FALSE;
		$this->content->input = $this->input;
	}

	protected function handle_form()
	{
		$validator = $this->setup_and_get_form_validator();

		if ($validator->validate()) {
			$this->send_email();
			$this->is_form_success = TRUE;
		} else {
			$this->content->error = $validator->errors('p');
		}
	}

	protected function send_email()
	{
		$email_view = $this->get_email_view();

		email::send(
			$this->email_address_to,
			'noreply@refactorteszt.hu',
			$this->email_subject,
			$email_view->render(),
			TRUE
		);
	}

	abstract protected function get_email_view();

	protected function setup_and_get_form_validator()
	{
		$validator = new Validator($_POST, $this->validation_rules);

		if (isset($_POST['legal']) || !isset($_POST['fullname']) || (trim($_POST['fullname']) != '') || !isset($_POST['address']) || (trim($_POST['address']) != 'Fake address to prevent illegal action in the form!'))
			$validator->trigger_error('Az Ön gépét rendszerünk reklámrobotnak tekintette, így kérését nem tudjuk elfogadni!');

		if (!isset($_POST['secure_token']) || !Auth::check_csrf_token($_POST['secure_token']))
			$validator->trigger_error('Az Ön gépéhez tartozó biztonsági kulcsot hibásnak találtuk - kérjük töltse újra az oldalt!');return $validator;

		return $validator;
	}

	protected function is_posted_data()
	{
		return isset($_POST['name']);
	}

	protected function purify_user_input()
	{
		$_POST = Input::instance()->xss_clean($_POST, 'htmlpurifier');
	}

	protected function initialize_content()
	{
		View::title($this->page->title);

		$this->content = new View($this->content_view_path);
		$this->content->page = $this->page;
	}


}