<h1>CakePHP component for uploading images</h1>
This is simple component that allow you to upload your images.

<h3>How to use</h3>

<strong>1.</strong> Connect component
<pre>
public $components = array('MyMadImage');
</pre>

<strong>2.</strong> Use it. Simple image uploading.
<pre>
if($this->request->is('post')) {
	if($this->MyMadImage->upload($_FILES)) {
		pr($this->MyMadImage->getResult());
	} else {
		pr($this->MyMadImage->getErrors());
	}
}
</pre>

Use <code>upload</code> function for uploading images. Code above will upload all images from <code>$_FILES</code> array, that we get from form.
If uploading was success it will return true, if failed - false.

Use <code>getResult</code> function for get array that contains urls uploaded images. If will call <code>getResult</code>:
<pre>
Array
(
    [result_urls] => Array
	(
		[0] => img\uploaded\86db1453b695b797536d64631ce374f4.png
	)
)
</pre>

If upload is failed you can use <code>getErrors</code> for get error message like array. Example:
<pre>
Array
(
    [0] => Array
        (
            [0] => Array
                (
                    [message] => There are no file to upload
                    [file_name] => 
                )

        )

)
</pre>
You will get this array if you will try to upload form without selected images

Another example. If you try upload not image you will get this message:
<pre>
Array
(
    [0] => Array
        (
            [0] => Array
                (
                    [message] => File type is forbidden
                    [file_name] => 1.zip
                )

        )

)
</pre>
