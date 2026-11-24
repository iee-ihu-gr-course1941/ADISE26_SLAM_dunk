const GET_LOBBIES_ENDPOINT = "http://127.0.0.1:8000/api/v1/lobbies"; //get all the lobbies
const CREATE_LOBBY_ENDPOINT = "http://127.0.0.1:8000/api/v1/lobbies"; //create a new lobby
const JOIN_LOBBY_ENDPOINT = "http://127.0.0.1:8000/api/v1/lobbies/{id}/join"; //join an existing lobby
const DELETE_LOBBY_ENDPOINT = "http://127.0.0.1:8000/api/v1/lobbies"; //delete a lobby you own

let listOfLobbies;
//Jquery 
$(document).ready(getLobbies);
//
window.initializeLobbies = initializeLobbies;

let token = JSON.parse(sessionStorage.getItem("token"));
function initializeLobbies(){
  listOfLobbies = $("#list-lobbies");
  if(!listOfLobbies){
    return;
  }
  $(document).ready(createLobbiesList);
 
}

function getLobbies(){
  $.ajax({
    url: GET_LOBBIES_ENDPOINT,
    accepts: "application/json",
    method: 'GET',
    headers: { Authorization: `Bearer ${token}` },
    success: createLobbiesList,
  }
  );
}

function createLobby(){
  $.ajax({
    url: CREATE_LOBBY_ENDPOINT,
    accepts: "application/json",
    method: "POST",
    dataType: 'json',
    headers: {Authorization: `Bearer ${token}`},
    data: JSON.stringify({turn_duration_seconds: 10}),
    success: show_result,
  });
}

function deleteLobby(){}

function joinLobby(){}

function show_result(data){
  console.log(data);
}

function createLobbiesList(data){
  $("#list-lobbies").append('<li>' + data + '</li>');
  
}


initializeLobbies();
