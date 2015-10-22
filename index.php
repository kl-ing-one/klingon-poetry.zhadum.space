<?php
function make_seed()
{
	list($usec, $sec) = explode(' ', microtime());

	return (float)$sec + ((float)$usec * 100000);
}

class KlIngOne
{
	private $model;

	public function __construct()
	{
		$db          = new DB\SQL('mysql:host=localhost;dbname=klingon_poetry_dx', '*****', '*****');
		$this->model = new DB\SQL\mapper($db, 'klingon_poetry_tbl');
	}

	private function get_poem($f3, $id)
	{
		$this->model->load(array('id_poem=:id_poem', array('id_poem' => (int)$id)));
		if ($this->model->dry())
		{
			$f3->error(404, 'I don\'t want to be the one. To see so far ahead. I have to live life looking back. To see the skies turn red. I don\'t want to be the one. To play this dangerous game');
			die();
		}
		$db_data = json_decode($this->model->poem, true);
		$poem    = $db_data['poem'];
		$f3->set('id_poem', $id);
		$f3->set('poem', $poem);
	}

	public function poem($f3)
	{
		$this->get_poem($f3, (int)$f3->get('PARAMS.idpoem'));
		$f3->set('do_analytics',1);
		$template=new Template;
		echo $template->render('ui.html');

	}

	public function generate($f3)
	{
		require_once('***/libs/htmlpurifier/library/HTMLPurifier.auto.php');
		$purifier = new HTMLPurifier();
		make_seed();
		$models = array('cv2/lm_lstm_epoch50.00_0.5080.t7', 'cv/lm_lstm_epoch46.00_0.7940.t7');
		$rnx    = array_rand($models, 1);
		$model  = $models[$rnx];
		$seed   = round(rand());

		$cmd                       = 'cd ***/char-rnn && th ***/char-rnn/sample.lua -verbose 0 -temperature 0.8 -gpuid -1 -seed ' . $seed . ' -length 2048 -primetext "<poem><html><head><meta charset=\\"utf-8\\"><style>body{background-color:#000;color:#0c0;}</style></head><body>" /home/drakh/klingon-poetry/' . $model;
		$postVars                  = array('cmd' => $cmd);
		$options                   = array(
			'method' => 'POST', 'content' => http_build_query($postVars)
		);
		$r                         = \Web::instance()->request('http://127.0.0.1:1337', $options);
		$clean_html                = $purifier->purify($r['body']);
		$poem                      = nl2br(trim($clean_html));
		$db_data                   = array('seed' => $seed, 'model' => $model, 'poem' => $poem);
		$data_to_save              = json_encode($db_data, JSON_UNESCAPED_UNICODE);
		$this->model->poem         = $data_to_save;
		$this->model->written_date = date('d.m.Y H:i:s');
		$this->model->save();

		$id       = $this->model->id_poem;
		$postVars = array('id' => $id);
		$options  = array(
			'method' => 'POST', 'content' => http_build_query($postVars)
		);
		$r        = \Web::instance()->request('http://127.0.0.1:1338', $options);
		$f3->reroute('/poem/' . $id);
	}
	/*
	public function thumbs()
	{
	    for($i=1;$i<=7;$i++)
	    {
		$postVars = array('id' => $i);
		$options  = array(
			'method' => 'POST', 'content' => http_build_query($postVars)
		);
		$r        = \Web::instance()->request('http://127.0.0.1:1338', $options);
		
	    }
	}
	*/
}

$f3 = require('/var/www/libs/fatfree/lib/base.php');
$f3->set('DEBUG', 0);
$f3->set('UI', 'ui/');
$f3->route('GET /', 'KlIngOne->generate');
$f3->route('GET /preview/@idpoem', 'KlIngOne->poem');
$f3->route('GET /poem/@idpoem', 'KlIngOne->poem');
//$f3->route('GET /thumbs','KlIngOne->thumbs');
$f3->run();
?>