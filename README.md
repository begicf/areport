## About Areport
Areport is a web application, which allows you to create an XBRL instance based on XBRL taxonomy which is created by DPM Architect.
This standard is recommended and implemented by EU regulators such as [EBA](https://eba.europa.eu/risk-analysis-and-data/reporting-frameworks) and [EIOPA](https://www.eiopa.europa.eu/tools-and-data/supervisory-reporting-dpm-and-xbrl_en).

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
- max_input_vars = 4000 


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
php artisan migrate:fresh --seed
```

Run the application
```
php artisan serve
```

## Import *.xml example
Import xml file in HTML Table Form - RC Notation
```xml
<?xml version="1.0" encoding="UTF-8"?>
<data>
  <table_C_01.00>
    <sheet_000>
      <c010r010>1</c010r010>
      <c010r015>2</c010r015>
      <c010r020>3</c010r020>
      <c010r030>4</c010r030>
      <c010r040>5</c010r040>
    </sheet_000>
  </table_C_01.00>
  <table_C_02.00>
    <sheet_000>
      <c010r010>6</c010r010>
      <c010r020>7</c010r020>
      <c010r030>8</c010r030>
      <c010r040>9</c010r040>
      <c010r050>10</c010r050>
    </sheet_000>
  </table_C_02.00>
</data>

```
## Import *.json example
Import json file in HTML Table Form - RC Notation
```json
{
    "table_C_01.00": {
      "sheet_000": {
        "c010r010": "1",
        "c010r015": "2",
        "c010r020": "3",
        "c010r030": "4",
        "c010r040": "5"
      }
    },
    "table_C_02.00": {
      "sheet_000": {
        "c010r010": "6",
        "c010r020": "7",
        "c010r030": "8",
        "c010r040": "9",
        "c010r040": "10"
      }
    }
  }
```
## Tutorial

[Video](https://www.youtube.com/watch?v=WdV35ywmjjM&feature=youtu.be)
