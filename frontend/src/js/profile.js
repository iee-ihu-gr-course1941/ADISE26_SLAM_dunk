import { handleError, readResponse } from "./login.js";
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
let me = JSON.parse(storedUser);
window.onload = initializePlayerProfile;

//Logos pou janastelno get gia to player profile info
//Epeidh ta data (px to token kai to username) ta apothikeuoume arxika sto session storage
//otan kapoios kanei succesfull login, alla auto to info den ginete update (dioti den kaneis kapoios monimos login)
//ara oi nikes,httes,isopalies kai to elo menei idio me auth thn methodo, ara ta janazhtame apo to server pou epikoinonei me thn bash dedomenon mas
async function initializePlayerProfile() {
  try {
    const response = await fetch("", {
      method: "GET",
      headers: {
        //Poly shmantiko na mpoune auta ta 2, eidika ta application json kai to api_token
        Authorization: `Bearer ${me.token}`,
        Accept: "application/json",
      },
    });
    const data = await readResponse(response);
    me = data;
    console.log(me);
  } catch (error) {
    handleError(error);
  }
}
