//Code for the IEE Login to 1st fetch the user code through the redirect url with a GET request to the apps-login endpoint in the
//Bakckend -> Routes -> api/v1/apps/login
//Non Authenticated Users cannot see the websites data being returned
const APPS_LOGIN_ENDPOINT = "http://127.0.0.1:8000/api/v1/apps-login";
const APPS_USER_INFORMATION_ENDPOINT = "http://127.0.0.1:8000/api/v1/profile";
let loginButton;
let errorMessage;
let info;
window.onload = getLoginReady;
//Poly sos na kanoume initialize ta pragmata mas me to window on load

function getLoginReady() {
  loginButton = document.getElementById("test-iee-login-button");
  errorMessage = document.getElementById("error-message");
  info = document.getElementById("info");
  loginButton.addEventListener("click", loginAppsRedirect);
  //opote to user token to pairnoume sto onload
  const token = new URLSearchParams(window.location.search).get("token");
  if (token) {
    getUserInfo(token);
  }
}

//To fetch einai mia function ths javascript pou mas epitrepei na kanoume request se ena endpoint
//gia plhrofories json,images, files klp, einai asychrono data fetching, kai mas epitrepei na epikoinomoume me alla web APIs.
//Pairnei san parametro to API endpoint pou theloume na xtuphsoume kai options, synithos mesa se auta einai kai h methodos, px POST,PUT,DELETE
//h GET einai proairetikh

function loginAppsRedirect() {
  //Redirect prokalei to page genika na kanei reload kai den krataei ta data kai den kalei ta functions
  window.location.href = APPS_LOGIN_ENDPOINT;
}

//Fetch function
async function getUserInfo(token) {
  try {
    const response = await fetch("http://127.0.0.1:8000/api/v1/profile", {
      headers: {
        Authorization: "Bearer " + token, //Poly shmantiko na mpoune auta ta 2, eidika ta application json
        Accept: "application/json",
      },
    });
    if (!response.ok) {
      throw new Error("Could not fetch data");
    }
    const data = await readResponse(response);
    showUserInfo(data);
  } catch (error) {
    console.log(error);
  }
}

//anti na kanoume 3 then exoume extra functions
function readResponse(response) {
  if (!response.ok) {
    throw new Error("Http " + response.status);
  }
  return response.json();
}

function showUserInfo(data) {
  console.log(data);
  info.innerHTML = data.username; //to data einai ena object, json, to opoio exei ola ta stoixeia pou me epistrefei to laravel backend, opos to username, id, pragmatiko onoma foithth klp, gia na ta emfanisoume prepei na poume data.x opou x to
  //onoma tou xarakthristikou pou theloume, genika sto json etsi exoume probasi
}

//Error handling
function showError(error) {
  errorMessage.textContent = "Error from server: " + error;
}
