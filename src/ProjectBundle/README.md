# ProjectBundle

## Project

### Create

- PUT("/project")

#### BODY

```json
{
    "name": "example name",
    "start_date": "2018-02-01",
    "end_date": "2018-03-03",
    "number_of_households": 2,
    "value": 5,
    "notes": "This is a note",
    "iso3": "FR"
}
```

### Edit

- POST("/project/{id}")

*id* : id of the project to be edited

#### BODY

```json
{
    "name": "example name (edited)",
    "start_date": "2018-04-01",
    "end_date": "2018-05-03",
    "number_of_households": 20,
    "value": 15,
    "notes": "This is a note (edited)",
    "iso3": "FR"
}
```



## Donor

### Create

- PUT("/donor")

#### BODY

```json
{
    "fullname": "example name",
    "shortname": "example name",
    "date_added": "2018-04-01 11:20:13",
    "notes": "This is a note"
}
```

### Edit

- POST("/donor/{id}")

*id* : id of the donor to be edited

#### BODY

```json
{
    "fullname": "example name (edited)",
    "shortname": "name  (edited)",
    "date_added": "2018-04-01 11:20:13",
    "notes": "This is a note (edited)"
}