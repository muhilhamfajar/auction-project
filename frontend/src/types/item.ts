export interface Item {
  id?: number;
  uuid?: string;
  name: string;
  description?: string | null;
  startingPrice?: string;
  auctionStartTime?: string;
  auctionEndTime?: string;
  status?: number;
  createdAt?: string
  updatedAt?: string
  medias?: ItemMedia[]
}

export interface ItemWithHighestBid extends Item {
  highestBid?: Bid | null;
}

export interface ItemMedia {
  id?: number;
  uuid?: string;
  name?: string | null;
  caption?: string | null;
  imageName?: string | null;
  imageFile?: File;
  item?: Item;
}

export interface Bid {
  id?: number;
  uuid?: string;
  amount?: string;
  bidTime?: string;
  isAutoBid?: boolean;
  bidder?: User;
  item?: Item;
}

export interface User {
  id?: number;
  uuid?: string;
  name?: string;
  username?: string;
}

export interface AutoBidConfig {
  id?: number;
  uuid?: string;
  user?: User
  reservedAmount?: number
  maxBidAmount?: number
  bidAlertPercentage?: number
}

export interface UserAutoBid {
  uuid?: string
  user?: User
  item?: Item
}

export interface UserNotification {
  uuid?: string
  user?: User
  createdAt?: string
  message?: string
  isRead?: boolean
}

export interface NewBid{
  amount: string;
  bidder: string;
}
