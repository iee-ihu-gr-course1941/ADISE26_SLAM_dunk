import * as THREE from 'three';
console.log(THREE);

const pieces = {
    "pawn-master":(color,x,y,z) => {
      const master = new THREE.Group();
      const hitboxGeometry = new THREE.ConeGeometry(0.4,2,32);
      const hitboxMaterial = new THREE.MeshBasicMaterial({
        visible: true,
        wireframe: true
      });
      const hitbox = new THREE.Mesh(hitboxGeometry,hitboxMaterial);
      master.add(hitbox);
      const masterPawnGeometry = new THREE.ConeGeometry();
      const masterPawnMaterial = new THREE.MeshStandardMaterial({
          color: color,
          side: THREE.DoubleSide
      });
      const masterPawn = new THREE.Mesh(masterPawnGeometry, masterPawnMaterial);
      masterPawn.castShadow = true;
      master.add(masterPawn);
      master.position.set(x,y,z);
      master.userData = {
          id: "pawn-master",
          x: x,
          y: y,
          z: z,
          name: "master",
          draggable: true
      };
      return master;
    },
    "pawn-student":(color,x,y,z) => {
      const student = new THREE.Group();
      const hitboxGeometry = new THREE.ConeGeometry(0.4,2,32);
      const hitboxMaterial = new THREE.MeshBasicMaterial({
        visible: true,
        wireframe: true
      });
      const hitbox = new THREE.Mesh(hitboxGeometry,hitboxMaterial);
      student.add(hitbox);
      const studentPawnGeometry = new THREE.ConeGeometry();
      const studentPawnMaterial = new THREE.MeshStandardMaterial({
          color: color,
          side: THREE.DoubleSide
      });
      const studentPawn = new THREE.Mesh(studentPawnGeometry, studentPawnMaterial);
      studentPawn.castShadow = true;
      student.add(studentPawn);
      student.position.set(x,y,z);
      student.userData = {
          id: "pawn-student",
          x: x,
          y: y,
          z: z,
          name: "student",
          draggable: true
      };
      return student;
    }
};

export function createPieces(assetId,color,x,y,z){
  if(assetId in pieces){
    return pieces[assetId](color,x,y,z);
  }
  else{
    console.log("Not a piece of the game");
    return undefined;
  }
}