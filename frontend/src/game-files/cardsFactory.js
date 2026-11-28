import * as THREE from 'three';
console.log(THREE);

const cards = {
      "player1-card1":() => {},
      "player1-card2":() => {},
      "player2-card1":() => {},
      "player2-card2":() => {},
      "neutral-card":() => {}   
}

export function createCards(assetId,x,y,z){
  if(assetId in cards){
    return cards[assetId](x,y,z);
  }
  else{
    console.log("Card does not exist");
    return undefined;
  }
}