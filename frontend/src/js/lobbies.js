const GET_LOBBIES_ENDPOINT = "http://127.0.0.1:8000/api/v1/lobbies"; //get all the lobbies
const CREATE_LOBBY_ENDPOINT = "http://127.0.0.1:8000/api/v1/lobbies"; //create a new lobby
const JOIN_LOBBY_ENDPOINT = "http://127.0.0.1:8000/api/v1/lobbies/{id}/join"; //join an existing lobby
const DELETE_LOBBY_ENDPOINT = "http://127.0.0.1:8000/api/v1/lobbies"; //delete a lobby you own
let listOfLobbies;

function initializeLobbies(){}
