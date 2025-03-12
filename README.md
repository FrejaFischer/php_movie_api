# PHP Movie API - Initial

Initial code for the PHP Movie API. It needs to be completed so that it becomes a RESTful API.

## Tools

MariaDB / PHP8

### Main usage:

http://_<server_name>_/kea-movie-manager-rest-api/_<endpoint>_

#### Endpoints:

| Method | Usage                     | Description                                                                   |
| ------ | :------------------------ | :---------------------------------------------------------------------------- |
| GET    | /                         | Returns the API description for GET methods                                   |
| GET    | /movies                   | Returns all movie IDs and names                                               |
| GET    | /movies?s=_<search_text>_ | Returns the IDs and names of those movies whose name contains _<search_text>_ |
| GET    | /movies/_<movie_id>_      | Returns the ID and name of the movie with ID _<movie_id>_                     |
| POST   | /movies                   | Adds a movie                                                                  |
| PUT    | /movies/_<movie_id>_      | Updates the name of the movie with ID _<movie_id>_                            |
| DELETE | /movies/_<movie_id>_      | Deletes the movie with ID _<movie_id>_                                        |

#### Sample Output:

```json
{
  "_total": 3,
  "data": [
    { "id": 24, "name": "Lord of the Rings: The Two Towers" },
    { "id": 1, "name": "Star Wars Episode IV: A New Hope" },
    { "id": 2, "name": "The Shape of Water" }
  ],
  "_links": [
    {
      "rel": "movies",
      "href": "<server_path>/kea-movie-manager-rest-api/movies{?name=}",
      "type": "GET"
    },
    {
      "rel": "movies",
      "href": "<server_path>/kea-movie-manager-rest-api/movies/{id}",
      "type": "GET"
    },
    {
      "rel": "movies",
      "href": "<server_path>/kea-movie-manager-rest-api/movies",
      "type": "POST"
    },
    {
      "rel": "movies",
      "href": "<server_path>/kea-movie-manager-rest-api/movies/{id}",
      "type": "PUT"
    },
    {
      "rel": "movies",
      "href": "<server_path>/kea-movie-manager-rest-api/movies{?name=}",
      "type": "DELETE"
    }
  ]
}
```
