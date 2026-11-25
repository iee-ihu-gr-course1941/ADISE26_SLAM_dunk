const GET_LOBBIES_ENDPOINT = "http://127.0.0.1:8000/api/v1/lobbies"; //get all the lobbies //create a new lobby
const JOIN_LOBBY_ENDPOINT = ``; //join an existing lobby
const DELETE_LOBBY_ENDPOINT = "http://127.0.0.1:8000/api/v1/lobbies"; //delete a lobby you own

let listOfLobbies;
window.initializeLobbies = initializeLobbies;
$(document).ready(getLobbies);
const storedUser = sessionStorage.getItem("token"); 
let token = JSON.parse(storedUser);
console.log(token);

function initializeLobbies(){
  listOfLobbies = $("#list-lobbies");
  if(!listOfLobbies){
    return;
  }
  $("#create-lobby-button").click(createLobby);
  $("#delete-lobby-button").click(deleteLobby);
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
  let data = {turn_duration_seconds:10};
  let sendData = JSON.stringify(data);
  $.ajax({
    url: GET_LOBBIES_ENDPOINT,
    method: 'POST',
    dataType: "json",
    data: sendData,
    headers: {Authorization: `Bearer ${token}`,accepts: 'application/json'},
    success: getLobbies
  });
}

function deleteLobby(){
  $.ajax({
    url: DELETE_LOBBY_ENDPOINT,
    method: 'DELETE',
    dataType: "json",
    headers: {Authorization: `Bearer ${token}`, accepts: 'application/json'},
    success: getLobbies
  });
}

function joinLobby(id){
  console.log(id);
  $.ajax({
    url: `http://127.0.0.1:8000/api/v1/lobbies/${id}/join`,
    method: 'POST',
    dataType: "json",
    headers: {Authorization: `Bearer ${token}`, accepts: 'application/json'},
  });
}

function show_result(data){
  listOfLobbies = data;
  console.log(listOfLobbies);
}

function createLobbiesList(data){
  console.log(data);
  listOfLobbies.empty();
  if(data.length === 0){
    show_message("No Lobbies to join!");
    return;
  }
  let i = 0;
  let elements = data.map(function (lobby){
    return createLobbyListElement(lobby);
  });
  listOfLobbies.append(elements);
}

function createLobbyListElement(lobby){
  let lobbyBox = document.createElement("div");
  lobbyBox.classList.add("lobby-box");


  let lobbyInfo = document.createElement("div");
  lobbyInfo.classList.add("lobby-info");

  let lobbyHostName = document.createElement("span");
  lobbyHostName.textContent = "Host: "+lobby.host_username;
  lobbyHostName.classList.add("lobby-host");

  let lobbyCreationTime = document.createElement("span");
  lobbyCreationTime.textContent = lobby.created_at;
  lobbyCreationTime.classList.add("lobby-time-created");


  let lobbyId = document.createElement("span");
  lobbyId.textContent = "id: "+lobby.id;
  lobbyId.classList.add("lobby-id");

  let lobbyTurnDurationSeconds = document.createElement("span");
  lobbyTurnDurationSeconds.textContent = "Turn time: "+lobby.turn_duration_seconds + "s";
  lobbyTurnDurationSeconds.classList.add("lobby-duration");

  let joinLobbyButton = document.createElement("button");
  joinLobbyButton.textContent = "Join";
  joinLobbyButton.classList.add("join-lobby-button");
  joinLobbyButton.addEventListener("click",() => joinLobby(lobby.id));

  lobbyInfo.append(lobbyHostName,lobbyTurnDurationSeconds,lobbyId,lobbyCreationTime,joinLobbyButton);
  lobbyBox.append(lobbyInfo);

  return lobbyBox;
}


function show_message(message){
  listOfLobbies.empty();
}

initializeLobbies();
