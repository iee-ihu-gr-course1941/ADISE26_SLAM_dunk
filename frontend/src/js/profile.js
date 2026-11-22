import { readResponse, showError } from "./login.js";
const APPS_USER_PROFILE_ENDPOINT = "http://127.0.0.1:8000/api/v1/profile";
let list; //arxikes metablhtes
let wins; // metablhth gia ta wins pou tha paroume apo to response tou server
let losses; // metablhth gia ta losses pou tha paroume apo to response tou server
let draws; // metablhth gia tis isopalies tou xrhsth pou tha paroume apo to response tou server
let last_active; // metablhth gia to pote ekane to teleutaio tou login
let elo; // metablhth gia to elo apo to response tou server
let username; // metablhth gia to username tou xrhsth sto apps
let full_name; // metablhth gia to plhrh onoma tou xrhsth sto apps
let iee_id; // metablhth gia to iee_id tou xrhsth sto apps
const storedUser = sessionStorage.getItem("user"); //Pairnoume ton stored user apo to session storage pou apothukeusame sto 1o login tou xrhsth
console.log(storedUser); //for testing purposes
console.log("Stored user retrieved from the session storage");
let me = JSON.parse(storedUser);
console.log(me); //gia testing
window.onload = initializePlayerProfile;
window.initializePlayerProfile = initializePlayerProfile;

//Logos pou janastelno get gia to player profile info
//Epeidh ta data (px to token kai to username) ta apothikeuoume arxika sto session storage
//otan kapoios kanei succesfull login, alla auto to info den ginete update (dioti den kaneis kapoios monimos login)
//ara oi nikes,httes,isopalies kai to elo menei idio me auth thn methodo, ara ta janazhtame apo to server pou epikoinonei me thn bash dedomenon mas
function initializePlayerProfile() {
  //tha ta gemiso me ta id ton html elements otan tha einai etoimo to html
  username = document.getElementById(""); //html id for username text
  wins = document.getElementById(""); //html id for wins text
  losses = document.getElementById(""); //html id for losses text
  elo = document.getElementById(""); //html id for elo text
  draws = document.getElementById(""); //html id for draws text
  last_active = document.getElementById(""); //html id for last active text
  full_name = document.getElementById(""); //html id for full_name text
  iee_id = document.getElementById(""); //html id for iee_id text
  getUserInfo();
}

async function getUserInfo() {
  try {
    const response = await fetch(APPS_USER_PROFILE_ENDPOINT, {
      method: "GET",
      headers: {
        Authorization: `Bearer ${me.token}`,
        Accept: "application/json",
      },
    });
    const data = await readResponse(response);
    me = data;
    console.log(me);
    inputUserData(me);
  } catch (error) {
    console.log(error);
    showError(error);
  }
}

function inputUserData(me) {
  username.textContent = me.username;
  iee_id.textContent = me.iee_id;
  full_name.textContent = me.full_name;
  wins.textContent = me.leaderboard.wins;
  elo.textContent = me.leaderboard.elo;
  draws.textContent = me.leaderboard.draws;
  losses.textContent = me.leaderboard.losses;
  last_active.textContent = me.last_active;
}
