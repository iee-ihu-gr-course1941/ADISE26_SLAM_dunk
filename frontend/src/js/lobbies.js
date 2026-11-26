const GET_LOBBIES_ENDPOINT = "http://127.0.0.1:8000/api/v1/lobbies"; //get all the lobbies //create a new lobby
const DELETE_LOBBY_ENDPOINT = "http://127.0.0.1:8000/api/v1/lobbies"; //delete a lobby you own

let listOfLobbies;
let errorTimeout = null;
let exists;
window.initializeLobbies = initializeLobbies;
$(document).ready(getLobbies);
const storedUser = sessionStorage.getItem("token"); 
let token = JSON.parse(storedUser);

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
    error: show_error_message
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
    success: getLobbies,
    error: show_error_message
  });
}

function deleteLobby(){
  $.ajax({
    url: DELETE_LOBBY_ENDPOINT,
    method: 'DELETE',
    dataType: "json",
    headers: {Authorization: `Bearer ${token}`, accepts: 'application/json'},
    success: getLobbies,
    error: show_error_message,
  });
}

function show_error_message(data){
  if(errorTimeout !==null){
    //An to timeoute den einai null, svise to timeout
    clearTimeout(errorTimeout);
  }
  //An uparxei to error message div mesa sto DOM
  if(exists){
      exists = false;
      errorMessage.remove();
      //Svisto kai bale to exists false, dhladh oti pleon den uparxei
  }
  
  let errorMessage = document.createElement("div");
  exists = true;//metablhth gia na doume an uparxei to errormessage div mesa sto DOM
  //profanos otan to dhmiourgoume to thetoume iso me true
  var x = data.responseJSON;
  errorMessage.textContent = x.message;
  errorMessage.classList.add("error-message");
  listOfLobbies.append(errorMessage);
  //Setaroume 2 deuterolepta timer event oste na kanoume remove to error message
  errorTimeoute = setTimeout(() => {
    errorMessage.remove();
    errorTimeout = null;
  }, 2000);
}

//AJAX function gia na kanei kapoios join to lobby me bash to id tou lobby pou pathsame
function joinLobby(id){
  console.log(id);
  $.ajax({
    url: `http://127.0.0.1:8000/api/v1/lobbies/${id}/join`,
    method: 'POST',
    dataType: "json",
    headers: {Authorization: `Bearer ${token}`, accepts: 'application/json'},
    error: show_error_message
  });
}

function show_result(data){
  listOfLobbies = data;
  console.log(listOfLobbies);
}

function createLobbiesList(data){
  listOfLobbies.empty();
  if(data.length === 0){
    show_message("No Lobbies to join!");
    return;
  }
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
  //Mexri edo einai apla h dhmiourgeia ton divs
  $("#joinLobbyButton").on("click", function(){ joinLobby(lobby.id); });
   //function sto join lobby
   //meso jquery

  lobbyInfo.append(lobbyHostName,lobbyTurnDurationSeconds,lobbyId,lobbyCreationTime,joinLobbyButton);
  lobbyBox.append(lobbyInfo);

  return lobbyBox;
}


function show_message(message){
  listOfLobbies.empty();
  let noLobbiesMessage = document.createElement("div");
  noLobbiesMessage.textContent = message;
  noLobbiesMessage.classList.add("no-lobbies");
  listOfLobbies.append(noLobbiesMessage);
}

initializeLobbies();
