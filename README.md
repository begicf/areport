## About Areport
Areport is a web application, which allows you to create an XBRL instance based on XBRL taxonomy which is created by DPM Architect.
This standard is recommended and implemented by EU regulators such as EBA and EIOPA.

It is test only on Linux and OSX environments

[DEMO](https://demo.areport.net/) 

Key futures:
- Web based solution
- XBRL table specification present through HTML forms
- Create XBRL instance 
- Import data from .xlsx formats
- Export data to .xlsx, .pdf, .html formats
- Easy Customisable and Scalable
- ...

The application uses a library [apreport-dpm-xbrl](https://github.com/begicf/areport-dpm-xbrl) for parsing XBRL taxonomy. The library is written independently, so it is applicable to any framework. 

The application Areport is based on Laravel framework.

## Requirements
- Server requirements for [Laravel framework](https://laravel.com/docs/7.x/installation#server-requirements)
- [Supported database](https://laravel.com/docs/7.x/database) - Areport use ORM Eloquent for storing data in database
- ZIP PHP Extension

## Configuration
To be able to upload large taxonomy package you need to set following parameters in
##### php.ini
- max_execution_time = 6000
- upload_max_filesize = 4000M
- post_max_size = 4000M

## Installation

```
git clone https://github.com/begicf/areport.git

composer update
```
- Set [env](https://laravel.com/docs/7.x/configuration#environment-variable-types) file
- additional .env variables
```
UPLOAD=1 #to enable update
LEI_CODE=12345678912345678912 #to set LEI CODE

```
To create database table, run this command
```
php artisan migrate:refresh
```
## Tutorial

[Video](https://www.youtube.com/watch?v=WdV35ywmjjM&feature=youtu.be)
