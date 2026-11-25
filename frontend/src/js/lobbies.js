const GET_LOBBIES_ENDPOINT = "http://127.0.0.1:8000/api/v1/lobbies"; //get all the lobbies //create a new lobby
const JOIN_LOBBY_ENDPOINT = `http://127.0.0.1:8000/api/v1/lobbies/id/join`; //join an existing lobby
const DELETE_LOBBY_ENDPOINT = "http://127.0.0.1:8000/api/v1/lobbies"; //delete a lobby you own

let listOfLobbies;
window.initializeLobbies = initializeLobbies;
$(document).ready(getLobbies);
const storedUser = sessionStorage.getItem("token"); 
let token = JSON.parse(storedUser);
console.log(token);

function initializeLobbies(){
  listOfLobbies = $("#list-lobbies-div");
  if(!listOfLobbies){
    return;
  }
  $("#create-lobby-button").click(createLobby);
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
  console.log("Button clicked");
  let data = {turn_duration_seconds:10};
  let sendData = JSON.stringify(data);
  console.log(sendData);
  $.ajax({
    url: GET_LOBBIES_ENDPOINT,
    method: 'POST',
    dataType: "json",
    data: sendData,
    headers: {Authorization: `Bearer ${token}`,accepts: 'application/json'},
    success: show_message
  });
}


function deleteLobby(){
  $.ajax({
    url: DELETE_LOBBY_ENDPOINT,
    method: 'DELETE',
    dataType: "json",
    headers: {Authorization: `Bearer ${token}`, accepts: 'application/json'},
    success: show_message
  });
}

function joinLobby(){}

function show_result(data){
  listOfLobbies = data;
  console.log(listOfLobbies);
}

function createLobbiesList(data){
  let i = 0;
  for(i=0;i<data.length;i++){
    $("#list-lobbies").append('<li>' + data[i].host_username + '</li>');
  }
}

function show_message(){
  console.log("success");
}

initializeLobbies();
