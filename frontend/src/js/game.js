import * as THREE from 'three';
console.log(THREE);
window.createScene = createScene;
function createScene(){
  //to div mesa sto opoio tha einai to game scene mas
  //einai anejarthto apo alla divs pou tuxon na mpoune mesa sto game scene
    const canvas = document.querySelector("canvas.render");
    if(!canvas){
      return;
    }
  //XRHSHMA CONSTANTS EDO PANO
    const PAWNS_COLOR = {
        RED: "#C41E3A", //Red player piece color
        BLUE: "#1565C0", //Blue player piece color
    };
    
    //Gia thn dhmiourgeia mias skhnhs kanoume ena neo instance tou scene object
    const scene = new THREE.Scene();
    //Gia thn camera kanoume neo instance tou camera object
    const camera = new THREE.PerspectiveCamera(
        75,
        window.innerWidth / window.innerHeight,
        0.1,
        50,
    );
    //oi times einai FOV, oi diastaseis, to near kai to far clipping plane
    camera.position.z = 5; //to position einai idiothta tou Object3D superclass thn opoia thn klhronomoun ola ta antikeimena ths threejs
    camera.position.y = 7; //exei xrhsimes idiothtes opos x,y,z
    camera.position.x = 10; //auta mas epitrepoun na orisoume tis suntetagmenes x,y,z enos antikeimenou to opoio klhronomei thn Object3D
    scene.add(camera); //epeita to camera instance mas prepei na mpei sto scene

    //Gia na kanoume render mia skhnh prepei na baloume kai ena renderer object
    //Parakato dhmiourgoume ena instance tou renderer object ths Three.JS
    const renderer = new THREE.WebGLRenderer({
        canvas: canvas,
        antialias: true,
    });
    renderer.shadowMap.enabled = true; //ENABLE SHADOWS SOS!!!
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.render(scene, camera);

    const axesHelper = new THREE.AxesHelper(5);
    scene.add(axesHelper);
}

createScene();