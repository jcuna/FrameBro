<?php
Markup::before('div', 'file-manager', ['id' => 'file-manager']);
{
    WebForm::open('file_upload', 'file_upload', null, 'form-inline', TRUE, 'POST', 'Select a file to upload');
    {
        WebForm::before('div', 'col-md-7 col-md-offset-2');
        {
            WebForm::before('div', 'form-group btn btn-info btn-file');
            {
                WebForm::field('image', 'file', null, null, 'Browse Files');
            }
            WebForm::after();
            WebForm::before();
            {
                WebForm::submit('Submit');
            }
            WebForm::after();
        }
        WebForm::after();
    }
    WebForm::close();

    if ($data) {
        Markup::element('hr');
        Markup::before('div', 'container');
        {
            Markup::element('h1', null, 'Uploaded Files');
            Markup::before('div', 'images-container');
            {
                Markup::before('ul', 'thumbnails');
                {
                    foreach ($data as $file) {
                        Markup::before('li', 'thumbnail-container');
                        {
                            Markup::element('span', ['style' => 'background-image: url(' . $file . ')', 'class' => 'thumbnail']);
                            Markup::element('a', ['href' => $file, 'title' => 'link', 'target' => '_blank'], 'Link to file');
                            Markup::element('button',
                                ['type' => 'button',
                                    'title' => 'Delete',
                                    'data-collect' => $file,
                                    'class' => 'delete-file btn btn-danger btn-xs center-block']
                                , 'Delete File');
                        }
                        Markup::after('li');
                    }
                }
                Markup::after('ul');
            }
            Markup::after('div');
        }
        Markup::after('div');
    }
}
Markup::after();
?>
